<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentRegistrationDivision;
use App\Services\Brackets\StandingsService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DivisionController extends Controller
{
    public function show(Tournament $tournament, TournamentDivision $division, StandingsService $standingsService): Response
    {
        $this->authorize('view', $tournament);
        abort_if($division->tournament_id !== $tournament->id, 404);

        $division->load([
            'rounds' => fn ($q) => $q->orderBy('round_number'),
            'groups' => fn ($q) => $q->orderBy('display_order'),
        ]);

        $entrantNames = $division->approvedEntrants()
            ->with('registration.player.user')
            ->get()
            ->mapWithKeys(fn (TournamentRegistrationDivision $e) => [$e->id => $this->entrantLabel($e, false)]);

        $matches = $division->matches()
            ->with(['entrant1', 'entrant2', 'round', 'group'])
            ->orderBy('match_number')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'round_id' => $m->round_id,
                'round_name' => $m->round?->name,
                'round_number' => $m->round?->round_number,
                'stage' => $m->round?->stage,
                'group_id' => $m->tournament_group_id,
                'group_name' => $m->group?->name,
                'match_number' => $m->match_number,
                'entrant1_id' => $m->entrant1_id,
                'entrant2_id' => $m->entrant2_id,
                'entrant1_name' => $this->entrantLabel($m->entrant1, $m->entrant1_is_bye),
                'entrant2_name' => $this->entrantLabel($m->entrant2, $m->entrant2_is_bye),
                'status' => $m->status,
                'winner_entrant_id' => $m->winner_entrant_id,
            ]);

        $groupsStandings = [];
        foreach ($division->groups as $group) {
            $groupsStandings[$group->id] = $this->attachNames($standingsService->standingsForGroup($group), $entrantNames);
        }

        $divisionStandings = in_array($division->format, ['round_robin', 'swiss'], true)
            ? $this->attachNames($standingsService->standingsForDivision($division), $entrantNames)
            : null;

        $swissRoundsGenerated = $division->rounds->where('stage', 'swiss')->count();

        return Inertia::render('tournaments/divisions/show', [
            'tournament' => $tournament,
            'division' => $division,
            'matches' => $matches,
            'groupsStandings' => $groupsStandings,
            'divisionStandings' => $divisionStandings,
            'swissRoundsGenerated' => $swissRoundsGenerated,
        ]);
    }

    private function entrantLabel(?TournamentRegistrationDivision $entrant, bool $isBye): string
    {
        if ($entrant) {
            $name = $entrant->registration->player->user->name;

            return $entrant->partner_name ? "{$name} / {$entrant->partner_name}" : $name;
        }

        return $isBye ? 'BYE' : 'Por definir';
    }

    private function attachNames(Collection $standings, Collection $entrantNames): array
    {
        return $standings->map(fn (array $row) => [
            ...$row,
            'name' => $entrantNames->get($row['entrant_id'], '—'),
        ])->values()->all();
    }
}
