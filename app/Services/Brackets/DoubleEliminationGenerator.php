<?php

namespace App\Services\Brackets;

use App\Models\Round;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Services\Brackets\Concerns\NamesEliminationRounds;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Standard double-elimination bracket: winners bracket + losers bracket + a single grand
 * final (the winners-bracket champion always wins the title on the first grand final match;
 * this simplified variant does not implement the "bracket reset" second final some
 * professional events use when the losers-bracket finalist wins the first grand final).
 */
class DoubleEliminationGenerator implements BracketGenerator
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

            $numWbRounds = (int) log($bracketSize, 2);

            [$wbRoundsMatches, $firstRoundMatches] = $this->buildWinnersBracket($division, $pairs, $numWbRounds);

            $lbFinalMatch = $this->buildLosersBracket($division, $wbRoundsMatches, $numWbRounds);

            $grandFinalRound = Round::create([
                'tournament_division_id' => $division->id,
                'stage' => 'grand_final',
                'round_number' => 1,
                'name' => 'Gran final',
            ]);

            $wbFinalMatch = $wbRoundsMatches[$numWbRounds][0];

            TournamentMatch::create([
                'tournament_division_id' => $division->id,
                'round_id' => $grandFinalRound->id,
                'match_number' => 1,
                'entrant1_source_match_id' => $wbFinalMatch->id,
                'entrant1_source_type' => 'winner',
                'entrant2_source_match_id' => $lbFinalMatch->id,
                'entrant2_source_type' => 'winner',
                'status' => 'pending',
            ]);

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

    /**
     * @param  array<int, array{0: mixed, 1: mixed}>  $pairs
     * @return array{0: array<int, array<int, TournamentMatch>>, 1: array<int, TournamentMatch>}
     */
    private function buildWinnersBracket(TournamentDivision $division, array $pairs, int $numWbRounds): array
    {
        $roundsMatches = [];
        $previousRoundMatches = [];
        $firstRoundMatches = [];

        for ($roundNumber = 1; $roundNumber <= $numWbRounds; $roundNumber++) {
            $matchesInRound = (int) (count($pairs) / (2 ** ($roundNumber - 1)));

            $round = Round::create([
                'tournament_division_id' => $division->id,
                'stage' => 'winners_bracket',
                'round_number' => $roundNumber,
                'name' => 'WB — '.$this->eliminationRoundName($matchesInRound),
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

            $roundsMatches[$roundNumber] = $currentRoundMatches;
            $previousRoundMatches = $currentRoundMatches;
        }

        return [$roundsMatches, $firstRoundMatches];
    }

    /**
     * @param  array<int, array<int, TournamentMatch>>  $wbRoundsMatches
     */
    private function buildLosersBracket(TournamentDivision $division, array $wbRoundsMatches, int $numWbRounds): TournamentMatch
    {
        if ($numWbRounds < 2) {
            return $wbRoundsMatches[1][0];
        }

        $lbRoundNumber = 1;
        $stageMatches = $wbRoundsMatches[1];
        $lastMajorRound = null;

        for ($k = 1; $k <= $numWbRounds - 1; $k++) {
            $sourceType = $k === 1 ? 'loser' : 'winner';

            $minorMatches = [];
            for ($i = 0; $i < count($stageMatches) / 2; $i++) {
                $source1 = $stageMatches[$i * 2];
                $source2 = $stageMatches[$i * 2 + 1];

                $minorMatches[] = TournamentMatch::create([
                    'tournament_division_id' => $division->id,
                    'round_id' => $this->makeLosersRound($division, $lbRoundNumber),
                    'match_number' => $i + 1,
                    'entrant1_source_match_id' => $source1->id,
                    'entrant1_source_type' => $sourceType,
                    'entrant2_source_match_id' => $source2->id,
                    'entrant2_source_type' => $sourceType,
                    'status' => 'pending',
                ]);
            }
            $lbRoundNumber++;

            $wbFeedRound = $wbRoundsMatches[$k + 1];
            $majorMatches = [];
            foreach ($minorMatches as $i => $minorMatch) {
                $majorMatches[] = TournamentMatch::create([
                    'tournament_division_id' => $division->id,
                    'round_id' => $this->makeLosersRound($division, $lbRoundNumber),
                    'match_number' => $i + 1,
                    'entrant1_source_match_id' => $minorMatch->id,
                    'entrant1_source_type' => 'winner',
                    'entrant2_source_match_id' => $wbFeedRound[$i]->id,
                    'entrant2_source_type' => 'loser',
                    'status' => 'pending',
                ]);
            }
            $lbRoundNumber++;

            $stageMatches = $majorMatches;
            $lastMajorRound = $majorMatches;
        }

        return $lastMajorRound[0];
    }

    private function makeLosersRound(TournamentDivision $division, int $roundNumber): int
    {
        return Round::create([
            'tournament_division_id' => $division->id,
            'stage' => 'losers_bracket',
            'round_number' => $roundNumber,
            'name' => 'LB — Ronda '.$roundNumber,
        ])->id;
    }
}
