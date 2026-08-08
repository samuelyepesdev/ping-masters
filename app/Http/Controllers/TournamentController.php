<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentRegistrationField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tournament::class);

        $query = Tournament::withCount(['divisions', 'registrations'])
            ->orderByDesc('start_date');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('created_by', $request->user()->id);
        }

        return Inertia::render('tournaments/index', [
            'tournaments' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tournament::class);

        return Inertia::render('tournaments/create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Tournament::class);

        $validated = $this->validateTournament($request);

        $tournament = Tournament::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'city' => $validated['city'] ?? null,
            'club_id' => $request->user()->club_id,
            'created_by' => $request->user()->id,
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'registration_opens_at' => $validated['registration_opens_at'] ?? null,
            'registration_closes_at' => $validated['registration_closes_at'] ?? null,
            'max_participants' => $validated['max_participants'] ?? null,
        ]);

        foreach ($validated['divisions'] as $index => $division) {
            $tournament->divisions()->create($this->divisionFields($division, $index));
        }

        foreach ($validated['registration_fields'] ?? [] as $index => $field) {
            $tournament->registrationFields()->create($this->fieldFields($field, $index));
        }

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', 'Torneo creado exitosamente.');
    }

    public function show(Tournament $tournament): Response
    {
        $this->authorize('view', $tournament);

        $tournament->load(['divisions', 'registrationFields']);
        $tournament->loadCount('registrations');

        $registrations = $tournament->registrations()
            ->with(['player.user', 'divisions.division'])
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return Inertia::render('tournaments/show', [
            'tournament' => $tournament,
            'registrations' => $registrations,
        ]);
    }

    public function edit(Tournament $tournament): Response
    {
        $this->authorize('update', $tournament);

        $tournament->load(['divisions', 'registrationFields']);

        return Inertia::render('tournaments/edit', [
            'tournament' => $tournament,
        ]);
    }

    public function update(Request $request, Tournament $tournament)
    {
        $this->authorize('update', $tournament);

        $validated = $this->validateTournament($request);

        $tournament->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'city' => $validated['city'] ?? null,
            'status' => $validated['status'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'registration_opens_at' => $validated['registration_opens_at'] ?? null,
            'registration_closes_at' => $validated['registration_closes_at'] ?? null,
            'max_participants' => $validated['max_participants'] ?? null,
        ]);

        $this->syncDivisions($tournament, $validated['divisions']);
        $this->syncRegistrationFields($tournament, $validated['registration_fields'] ?? []);

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', 'Torneo actualizado exitosamente.');
    }

    public function destroy(Tournament $tournament)
    {
        $this->authorize('delete', $tournament);

        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', 'Torneo eliminado.');
    }

    private function validateTournament(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'venue' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'status' => 'required|in:draft,registration_open,registration_closed,in_progress,completed,cancelled',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_opens_at' => 'nullable|date',
            'registration_closes_at' => 'nullable|date|after_or_equal:registration_opens_at',
            'max_participants' => 'nullable|integer|min:1',

            'divisions' => 'required|array|min:1',
            'divisions.*.id' => 'nullable|integer',
            'divisions.*.name' => 'required|string|max:255',
            'divisions.*.category_type' => 'required|in:singles,doubles,team',
            'divisions.*.gender_category' => 'required|in:open,male,female,mixed',
            'divisions.*.min_age' => 'nullable|integer|min:0|max:120',
            'divisions.*.max_age' => 'nullable|integer|min:0|max:120',
            'divisions.*.format' => 'required|in:single_elimination,double_elimination,round_robin,swiss,group_knockout',
            'divisions.*.best_of' => 'required|integer|in:5,7',
            'divisions.*.points_to_win' => 'required|integer|min:1|max:99',
            'divisions.*.group_size' => 'nullable|integer|min:3|max:16',
            'divisions.*.advance_per_group' => 'nullable|integer|min:1|max:8',
            'divisions.*.swiss_rounds' => 'nullable|integer|min:1|max:20',
            'divisions.*.max_participants' => 'nullable|integer|min:2',
            'divisions.*.seed_by_rating' => 'boolean',

            'registration_fields' => 'nullable|array',
            'registration_fields.*.id' => 'nullable|integer',
            'registration_fields.*.label' => 'required|string|max:255',
            'registration_fields.*.field_type' => 'required|in:'.implode(',', TournamentRegistrationField::TYPES),
            'registration_fields.*.options' => 'nullable|array',
            'registration_fields.*.options.*' => 'string|max:255',
            'registration_fields.*.placeholder' => 'nullable|string|max:255',
            'registration_fields.*.help_text' => 'nullable|string',
            'registration_fields.*.is_required' => 'boolean',
        ]);
    }

    private function divisionFields(array $division, int $index): array
    {
        return [
            'name' => $division['name'],
            'category_type' => $division['category_type'],
            'gender_category' => $division['gender_category'],
            'min_age' => $division['min_age'] ?? null,
            'max_age' => $division['max_age'] ?? null,
            'format' => $division['format'],
            'best_of' => $division['best_of'],
            'points_to_win' => $division['points_to_win'],
            'group_size' => $division['group_size'] ?? null,
            'advance_per_group' => $division['advance_per_group'] ?? null,
            'swiss_rounds' => $division['swiss_rounds'] ?? null,
            'max_participants' => $division['max_participants'] ?? null,
            'seed_by_rating' => $division['seed_by_rating'] ?? true,
            'display_order' => $index,
        ];
    }

    private function fieldFields(array $field, int $index): array
    {
        return [
            'label' => $field['label'],
            'field_type' => $field['field_type'],
            'options' => in_array($field['field_type'], TournamentRegistrationField::CHOICE_TYPES, true)
                ? ($field['options'] ?? [])
                : null,
            'placeholder' => $field['placeholder'] ?? null,
            'help_text' => $field['help_text'] ?? null,
            'is_required' => $field['is_required'] ?? true,
            'display_order' => $index,
        ];
    }

    private function syncDivisions(Tournament $tournament, array $divisions): void
    {
        $existingIds = $tournament->divisions()->pluck('id')->toArray();
        $incomingIds = collect($divisions)->pluck('id')->filter()->toArray();
        $toDelete = array_diff($existingIds, $incomingIds);

        if ($toDelete !== []) {
            $tournament->divisions()->whereIn('id', $toDelete)->delete();
        }

        foreach ($divisions as $index => $division) {
            $fields = $this->divisionFields($division, $index);

            if (! empty($division['id'])) {
                $tournament->divisions()->where('id', $division['id'])->update($fields);
            } else {
                $tournament->divisions()->create($fields);
            }
        }
    }

    private function syncRegistrationFields(Tournament $tournament, array $fields): void
    {
        $existingIds = $tournament->registrationFields()->pluck('id')->toArray();
        $incomingIds = collect($fields)->pluck('id')->filter()->toArray();
        $toDelete = array_diff($existingIds, $incomingIds);

        if ($toDelete !== []) {
            $tournament->registrationFields()->whereIn('id', $toDelete)->delete();
        }

        foreach ($fields as $index => $field) {
            $data = $this->fieldFields($field, $index);

            if (! empty($field['id'])) {
                $tournament->registrationFields()->where('id', $field['id'])->update($data);
            } else {
                $tournament->registrationFields()->create($data);
            }
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Tournament::where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$i;
        }

        return $slug;
    }
}
