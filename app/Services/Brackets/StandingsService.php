<?php

namespace App\Services\Brackets;

use App\Models\TournamentDivision;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;
use Illuminate\Support\Collection;

class StandingsService
{
    /**
     * @return Collection<int, array{entrant_id: int, played: int, wins: int, losses: int, points: int}>
     */
    public function standingsForGroup(TournamentGroup $group): Collection
    {
        $matches = TournamentMatch::where('tournament_group_id', $group->id)->get();

        $entrantIds = $matches->flatMap(fn (TournamentMatch $match) => [$match->entrant1_id, $match->entrant2_id])
            ->filter()
            ->unique()
            ->values();

        return $this->compute($entrantIds, $matches);
    }

    /**
     * @return Collection<int, array{entrant_id: int, played: int, wins: int, losses: int, points: int}>
     */
    public function standingsForDivision(TournamentDivision $division): Collection
    {
        $entrantIds = $division->approvedEntrants()->pluck('id');
        $matches = $division->matches()->get();

        return $this->compute($entrantIds, $matches);
    }

    /**
     * @param  Collection<int, int>  $entrantIds
     * @param  Collection<int, TournamentMatch>  $matches
     * @return Collection<int, array{entrant_id: int, played: int, wins: int, losses: int, points: int}>
     */
    private function compute(Collection $entrantIds, Collection $matches): Collection
    {
        $rows = [];

        foreach ($entrantIds as $id) {
            $rows[$id] = [
                'entrant_id' => $id,
                'played' => 0,
                'wins' => 0,
                'losses' => 0,
                'points' => 0,
            ];
        }

        foreach ($matches as $match) {
            if ($match->status !== 'completed' && $match->status !== 'walkover') {
                continue;
            }

            if (! $match->winner_entrant_id || ! isset($rows[$match->winner_entrant_id])) {
                continue;
            }

            $rows[$match->winner_entrant_id]['played']++;
            $rows[$match->winner_entrant_id]['wins']++;
            $rows[$match->winner_entrant_id]['points'] += 2;

            if ($match->loser_entrant_id && isset($rows[$match->loser_entrant_id])) {
                $rows[$match->loser_entrant_id]['played']++;
                $rows[$match->loser_entrant_id]['losses']++;
            }
        }

        return collect($rows)->values()->sortByDesc(fn (array $row) => [$row['points'], $row['wins']])->values();
    }
}
