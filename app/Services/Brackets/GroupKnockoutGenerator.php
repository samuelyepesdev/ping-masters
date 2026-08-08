<?php

namespace App\Services\Brackets;

use App\Models\Round;
use App\Models\TournamentDivision;
use App\Models\TournamentGroup;
use App\Models\TournamentMatch;
use App\Services\Brackets\Concerns\BuildsRoundRobinSchedule;
use App\Services\Brackets\Concerns\NamesEliminationRounds;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GroupKnockoutGenerator implements BracketGenerator
{
    use BuildsRoundRobinSchedule;
    use NamesEliminationRounds;

    public function __construct(private readonly SeedingService $seeding) {}

    public function generate(TournamentDivision $division): void
    {
        $entrants = $this->seeding->seededEntrants($division);

        if ($entrants->count() < 4) {
            throw new RuntimeException('Se necesitan al menos 4 inscritos aprobados para grupos + eliminación.');
        }

        $groupSize = $division->group_size ?: 4;
        $advancePerGroup = $division->advance_per_group ?: 2;
        $numGroups = max(2, (int) ceil($entrants->count() / $groupSize));

        DB::transaction(function () use ($division, $entrants, $numGroups, $advancePerGroup) {
            $groupNames = range('A', 'Z');
            $groupsEntrants = array_fill(0, $numGroups, []);

            $g = 0;
            $direction = 1;
            foreach ($entrants as $entrant) {
                $groupsEntrants[$g][] = $entrant;

                if ($direction === 1) {
                    $g++;
                    if ($g === $numGroups) {
                        $g = $numGroups - 1;
                        $direction = -1;
                    }
                } else {
                    $g--;
                    if ($g < 0) {
                        $g = 0;
                        $direction = 1;
                    }
                }
            }

            $groups = [];
            foreach ($groupsEntrants as $index => $members) {
                $groups[] = [
                    'model' => TournamentGroup::create([
                        'tournament_division_id' => $division->id,
                        'name' => 'Grupo '.$groupNames[$index],
                        'display_order' => $index,
                    ]),
                    'members' => $members,
                ];
            }

            $this->createGroupStageMatches($division, $groups);
            $this->createKnockoutStage($division, $groups, $advancePerGroup);

            $division->update(['status' => 'drawn']);
        });
    }

    /**
     * @param  array<int, array{model: TournamentGroup, members: array}>  $groups
     */
    private function createGroupStageMatches(TournamentDivision $division, array $groups): void
    {
        $schedules = array_map(fn (array $group) => $this->buildRoundRobinSchedule($group['members']), $groups);
        $maxRounds = max(array_map('count', $schedules));

        for ($roundIndex = 0; $roundIndex < $maxRounds; $roundIndex++) {
            $round = Round::create([
                'tournament_division_id' => $division->id,
                'stage' => 'group_stage',
                'round_number' => $roundIndex + 1,
                'name' => 'Ronda de grupos '.($roundIndex + 1),
            ]);

            $matchNumber = 1;

            foreach ($groups as $groupIndex => $group) {
                $pairs = $schedules[$groupIndex][$roundIndex] ?? [];

                foreach ($pairs as [$a, $b]) {
                    if ($a === null || $b === null) {
                        continue;
                    }

                    TournamentMatch::create([
                        'tournament_division_id' => $division->id,
                        'round_id' => $round->id,
                        'tournament_group_id' => $group['model']->id,
                        'match_number' => $matchNumber++,
                        'entrant1_id' => $a->id,
                        'entrant2_id' => $b->id,
                        'status' => 'ready',
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<int, array{model: TournamentGroup, members: array}>  $groups
     */
    private function createKnockoutStage(TournamentDivision $division, array $groups, int $advancePerGroup): void
    {
        $qualifierSlots = [];

        for ($rank = 1; $rank <= $advancePerGroup; $rank++) {
            foreach ($groups as $group) {
                if (count($group['members']) >= $rank) {
                    $qualifierSlots[] = ['group' => $group['model'], 'rank' => $rank];
                }
            }
        }

        $total = count($qualifierSlots);

        if ($total < 2) {
            throw new RuntimeException('No hay suficientes clasificados para la fase eliminatoria.');
        }

        $bracketSize = $this->seeding->nextPowerOfTwo($total);
        $positions = $this->seeding->seedPositions($bracketSize);

        $slotMap = [];
        foreach ($positions as $seedNumber) {
            $slotMap[$seedNumber] = $qualifierSlots[$seedNumber - 1] ?? null;
        }

        $pairs = [];
        for ($i = 0; $i < $bracketSize / 2; $i++) {
            $pairs[] = [$slotMap[$positions[$i * 2]] ?? null, $slotMap[$positions[$i * 2 + 1]] ?? null];
        }

        $numRounds = (int) log($bracketSize, 2);
        $previousRoundMatches = [];

        for ($roundNumber = 1; $roundNumber <= $numRounds; $roundNumber++) {
            $matchesInRound = (int) ($bracketSize / (2 ** $roundNumber));

            $round = Round::create([
                'tournament_division_id' => $division->id,
                'stage' => 'main_bracket',
                'round_number' => $roundNumber,
                'name' => $this->eliminationRoundName($matchesInRound),
            ]);

            $currentRoundMatches = [];

            for ($matchNumber = 1; $matchNumber <= $matchesInRound; $matchNumber++) {
                if ($roundNumber === 1) {
                    [$slot1, $slot2] = $pairs[$matchNumber - 1];

                    $match = TournamentMatch::create([
                        'tournament_division_id' => $division->id,
                        'round_id' => $round->id,
                        'match_number' => $matchNumber,
                        'entrant1_source_group_id' => $slot1['group']->id ?? null,
                        'entrant1_source_group_rank' => $slot1['rank'] ?? null,
                        'entrant1_is_bye' => $slot1 === null,
                        'entrant2_source_group_id' => $slot2['group']->id ?? null,
                        'entrant2_source_group_rank' => $slot2['rank'] ?? null,
                        'entrant2_is_bye' => $slot2 === null,
                        'status' => 'pending',
                    ]);
                } else {
                    $source1 = $previousRoundMatches[$matchNumber * 2 - 2];
                    $source2 = $previousRoundMatches[$matchNumber * 2 - 1];

                    $match = TournamentMatch::create([
                        'tournament_division_id' => $division->id,
                        'round_id' => $round->id,
                        'match_number' => $matchNumber,
                        'entrant1_source_match_id' => $source1->id,
                        'entrant1_source_type' => 'winner',
                        'entrant2_source_match_id' => $source2->id,
                        'entrant2_source_type' => 'winner',
                        'status' => 'pending',
                    ]);
                }

                $currentRoundMatches[] = $match;
            }

            $previousRoundMatches = $currentRoundMatches;
        }
    }
}
