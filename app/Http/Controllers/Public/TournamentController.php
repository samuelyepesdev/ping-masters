<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentRegistrationField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $player = auth()->user()->player;

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

        $player = Player::firstOrCreate(['user_id' => $user->id], ['club_id' => $user->club_id]);

        if (! $user->hasRole('player')) {
            $user->assignRole('player');
        }

        if ($tournament->registrations()->where('player_id', $player->id)->exists()) {
            return back()->with('error', 'Ya estás inscrito en este torneo.');
        }

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

        return redirect()->route('public.tournaments.show', $tournament)
            ->with('success', '¡Inscripción enviada! Un organizador la revisará pronto.');
    }
}
