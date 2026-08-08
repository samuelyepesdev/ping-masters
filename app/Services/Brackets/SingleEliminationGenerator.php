<?php

namespace App\Services\Brackets;

use App\Models\Round;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Services\Brackets\Concerns\NamesEliminationRounds;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SingleEliminationGenerator implements BracketGenerator
{
    use NamesEliminationRounds;

    public function __construct(
        private readonly SeedingService $seeding,
        private readonly BracketAdvancementService $advancement,
    ) {}

    public function generate(TournamentDivision $division): void
    {
        $entrants = $this->seeding->seededEntrants($division);

        if ($entrants->count() < 2) {
            throw new RuntimeException('Se necesitan al menos 2 inscritos aprobados para generar el sorteo.');
        }

        DB::transaction(function () use ($division, $entrants) {
            $bracketSize = $this->seeding->nextPowerOfTwo($entrants->count());
            $positions = $this->seeding->seedPositions($bracketSize);

            $seedMap = [];
            foreach ($positions as $seedNumber) {
                $seedMap[$seedNumber] = $entrants->get($seedNumber - 1);
            }

            $pairs = [];
            for ($i = 0; $i < $bracketSize / 2; $i++) {
                $pairs[] = [$seedMap[$positions[$i * 2]] ?? null, $seedMap[$positions[$i * 2 + 1]] ?? null];
            }

            $pairs = $this->seeding->avoidSameClubFirstRoundPairs($pairs);

            $numRounds = (int) log($bracketSize, 2);
            $previousRoundMatches = [];
            $firstRoundMatches = [];

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
                        [$entrant1, $entrant2] = $pairs[$matchNumber - 1];

                        $match = TournamentMatch::create([
                            'tournament_division_id' => $division->id,
                            'round_id' => $round->id,
                            'match_number' => $matchNumber,
                            'entrant1_id' => $entrant1?->id,
                            'entrant2_id' => $entrant2?->id,
                            'entrant1_is_bye' => $entrant1 === null,
                            'entrant2_is_bye' => $entrant2 === null,
                            'status' => ($entrant1 && $entrant2) ? 'ready' : 'pending',
                        ]);

                        $firstRoundMatches[] = $match;
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

            // Now that the whole bracket tree exists, resolve first-round byes so their
            // winners propagate into the already-created round 2 slots.
            foreach ($firstRoundMatches as $match) {
                if ($match->entrant1_is_bye && $match->entrant2_id) {
                    $this->advancement->recordResult($match, $match->entrant2_id);
                } elseif ($match->entrant2_is_bye && $match->entrant1_id) {
                    $this->advancement->recordResult($match, $match->entrant1_id);
                }
            }

            $division->update(['status' => 'drawn']);
        });
    }
}
