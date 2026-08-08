<?php

namespace App\Services\Brackets;

use App\Models\TournamentDivision;
use App\Models\TournamentRegistrationDivision;
use Illuminate\Support\Collection;

class SeedingService
{
    /**
     * Approved entrants for a division, ordered from strongest (index 0) to weakest seed.
     *
     * @return Collection<int, TournamentRegistrationDivision>
     */
    public function seededEntrants(TournamentDivision $division): Collection
    {
        $entrants = $division->approvedEntrants()->with('registration.player.club')->get();

        if (! $division->seed_by_rating) {
            return $entrants->shuffle()->values();
        }

        return $entrants
            ->sortByDesc(fn (TournamentRegistrationDivision $entrant) => $entrant->seed_rating_snapshot ?? 0)
            ->values();
    }

    /**
     * Standard "avoid early rematches of top seeds" bracket seed order.
     * For size=8 this returns [1, 8, 4, 5, 2, 7, 3, 6].
     *
     * @return int[]
     */
    public function seedPositions(int $bracketSize): array
    {
        $positions = [1, 2];

        while (count($positions) < $bracketSize) {
            $total = count($positions) * 2 + 1;
            $next = [];

            foreach ($positions as $position) {
                $next[] = $position;
                $next[] = $total - $position;
            }

            $positions = $next;
        }

        return $positions;
    }

    public function nextPowerOfTwo(int $count): int
    {
        return $count <= 1 ? 1 : 2 ** (int) ceil(log($count, 2));
    }

    public function clubIdFor(?TournamentRegistrationDivision $entrant): ?int
    {
        return $entrant?->registration?->player?->club_id;
    }

    /**
     * Given ordered round-1 pairs (each a 2-tuple of entrants, either may be null for a bye),
     * try to reduce first-round matchups between entrants from the same club by swapping
     * entrants between neighbouring pairs. The pair containing seed 1 and the pair containing
     * the lowest seed are left untouched to preserve top-seed bracket separation.
     *
     * @param  array<int, array{0: ?TournamentRegistrationDivision, 1: ?TournamentRegistrationDivision}>  $pairs
     * @return array<int, array{0: ?TournamentRegistrationDivision, 1: ?TournamentRegistrationDivision}>
     */
    public function avoidSameClubFirstRoundPairs(array $pairs): array
    {
        $lastIndex = count($pairs) - 1;

        for ($i = 1; $i < $lastIndex; $i++) {
            [$a1, $a2] = $pairs[$i];

            if ($a1 === null || $a2 === null) {
                continue;
            }

            if ($this->clubIdFor($a1) === null || $this->clubIdFor($a1) !== $this->clubIdFor($a2)) {
                continue;
            }

            $swapped = $this->trySwapToResolveConflict($pairs, $i, $lastIndex);

            if ($swapped !== null) {
                $pairs = $swapped;
            }
        }

        return $pairs;
    }

    /**
     * @param  array<int, array{0: ?TournamentRegistrationDivision, 1: ?TournamentRegistrationDivision}>  $pairs
     * @return array<int, array{0: ?TournamentRegistrationDivision, 1: ?TournamentRegistrationDivision}>|null
     */
    private function trySwapToResolveConflict(array $pairs, int $conflictIndex, int $lastIndex): ?array
    {
        foreach ([$conflictIndex - 1, $conflictIndex + 1] as $neighbourIndex) {
            if ($neighbourIndex <= 0 || $neighbourIndex >= $lastIndex) {
                continue;
            }

            $neighbour = $pairs[$neighbourIndex];
            $conflictClub = $this->clubIdFor($pairs[$conflictIndex][0]);

            foreach ([0, 1] as $slot) {
                $candidate = $neighbour[$slot];

                if ($candidate === null || $this->clubIdFor($candidate) === $conflictClub) {
                    continue;
                }

                $other = $pairs[$conflictIndex][1];
                $newNeighbourClub = $this->clubIdFor($pairs[$conflictIndex][1]);

                if ($newNeighbourClub !== null && $newNeighbourClub === $this->clubIdFor($neighbour[1 - $slot])) {
                    continue;
                }

                $pairs[$conflictIndex][1] = $candidate;
                $neighbour[$slot] = $other;
                $pairs[$neighbourIndex] = $neighbour;

                return $pairs;
            }
        }

        return null;
    }
}
