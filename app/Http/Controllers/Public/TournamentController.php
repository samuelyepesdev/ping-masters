<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\TournamentRegistrationConfirmation;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentRegistrationField;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(): Response
    {
        $tournaments = Tournament::whereIn('status', ['registration_open', 'registration_closed', 'in_progress', 'completed'])
            ->withCount(['divisions', 'registrations'])
            ->orderByDesc('start_date')
            ->paginate(12);

        return Inertia::render('public/tournaments/index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function show(Tournament $tournament): Response
    {
        abort_if($tournament->status === 'draft', 404);

        $tournament->load('divisions');
        $tournament->loadCount('registrations');

        $userRegistration = null;

        if (auth()->check() && $player = auth()->user()->player) {
            $userRegistration = $tournament->registrations()
                ->where('player_id', $player->id)
                ->with('divisions.division')
                ->first();
        }

        return Inertia::render('public/tournaments/show', [
            'tournament' => $tournament,
            'userRegistration' => $userRegistration,
            'isRegistrationOpen' => $tournament->isRegistrationOpen(),
        ]);
    }

    public function register(Tournament $tournament): Response|RedirectResponse
    {
        abort_unless($tournament->isRegistrationOpen(), 404);

        $user = auth()->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Debes verificar tu correo electrónico antes de inscribirte a otro torneo.');
        }

        $player = $user?->player;

        if ($player && $tournament->registrations()->where('player_id', $player->id)->exists()) {
            return redirect()->route('public.tournaments.show', $tournament)
                ->with('error', 'Ya estás inscrito en este torneo.');
        }

        $tournament->load('divisions', 'registrationFields');

        return Inertia::render('public/tournaments/register', [
            'tournament' => $tournament,
        ]);
    }

    public function store(Request $request, Tournament $tournament): RedirectResponse
    {
        abort_unless($tournament->isRegistrationOpen(), 404);

        $user = $request->user();

        // A brand-new account created moments ago by the guest branch below is grandfathered
        // in for the registration that created it — blocking that would be a chicken-and-egg
        // problem. Only an already-existing, still-unverified account is blocked here.
        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Debes verificar tu correo electrónico antes de inscribirte a otro torneo.');
        }

        $rules = [
            'divisions' => 'required|array|min:1',
            'divisions.*.division_id' => [
                'required',
                Rule::exists('tournament_divisions', 'id')->where('tournament_id', $tournament->id),
            ],
            'divisions.*.partner_name' => 'nullable|string|max:255',
            'divisions.*.partner_club' => 'nullable|string|max:255',
            'responses' => 'array',
        ];

        if (! $user) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|string|lowercase|email|max:255';
            $rules['phone'] = 'nullable|string|max:50';
        }

        $tournament->load('registrationFields');

        foreach ($tournament->registrationFields as $field) {
            $key = "responses.{$field->id}";
            $rule = $field->is_required ? ['required'] : ['nullable'];

            $rule[] = match ($field->field_type) {
                'number' => 'numeric',
                'email' => 'email',
                'date' => 'date',
                'checkbox' => 'boolean',
                'checkbox_group' => 'array',
                default => 'string',
            };

            $rules[$key] = $rule;

            if ($field->field_type === 'checkbox_group') {
                $rules["{$key}.*"] = 'string';
            }
        }

        $validated = $request->validate($rules);

        if ($tournament->max_participants) {
            $activeCount = $tournament->registrations()->whereIn('status', ['pending', 'approved'])->count();
            if ($activeCount >= $tournament->max_participants) {
                return back()->with('error', 'El torneo alcanzó el cupo máximo de participantes.');
            }
        }

        $setPasswordUrl = null;
        $accountCreated = false;

        if (! $user) {
            $existing = User::where('email', $validated['email'])->first();

            if ($existing) {
                return back()
                    ->withErrors(['email' => 'Ya existe una cuenta con este correo. Inicia sesión para continuar con la inscripción.'])
                    ->withInput();
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make(Str::random(40)),
            ]);

            event(new Registered($user));
            Auth::login($user);

            $accountCreated = true;
            $token = Password::broker()->createToken($user);
            $setPasswordUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
        }

        $player = Player::firstOrCreate(['user_id' => $user->id], ['club_id' => $user->club_id]);

        if (! $user->hasRole('player')) {
            $user->assignRole('player');
        }

        if ($tournament->registrations()->where('player_id', $player->id)->exists()) {
            return back()->with('error', 'Ya estás inscrito en este torneo.');
        }

        $divisionNames = $tournament->divisions()
            ->whereIn('id', collect($validated['divisions'])->pluck('division_id'))
            ->pluck('name')
            ->all();

        DB::transaction(function () use ($tournament, $player, $validated) {
            $registration = $tournament->registrations()->create([
                'player_id' => $player->id,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            foreach ($validated['divisions'] as $division) {
                $registration->divisions()->create([
                    'tournament_division_id' => $division['division_id'],
                    'partner_name' => $division['partner_name'] ?? null,
                    'partner_club' => $division['partner_club'] ?? null,
                    'seed_rating_snapshot' => $player->rating_current,
                ]);
            }

            foreach ($validated['responses'] ?? [] as $fieldId => $value) {
                $field = TournamentRegistrationField::find($fieldId);

                if (! $field || $field->tournament_id !== $tournament->id) {
                    continue;
                }

                $registration->responses()->create([
                    'tournament_registration_field_id' => $fieldId,
                    'value' => is_array($value) ? json_encode($value) : $value,
                ]);
            }
        });

        try {
            Mail::to($user->email)->send(new TournamentRegistrationConfirmation($user, $tournament, $divisionNames, $setPasswordUrl));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('public.tournaments.show', $tournament)
            ->with('success', $accountCreated
                ? '¡Inscripción enviada! Creamos tu cuenta y te enviamos un correo para que definas tu contraseña.'
                : '¡Inscripción enviada! Un organizador la revisará pronto.');
    }
}
