<?php

namespace App\Services\Brackets;

use App\Models\Round;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SwissGenerator implements BracketGenerator
{
    public function __construct(
        private readonly SeedingService $seeding,
        private readonly StandingsService $standings,
    ) {}

    public function generate(TournamentDivision $division): void
    {
        $entrants = $this->seeding->seededEntrants($division);

        if ($entrants->count() < 2) {
            throw new RuntimeException('Se necesitan al menos 2 inscritos aprobados para generar el sorteo.');
        }

        DB::transaction(function () use ($division, $entrants) {
            $n = $entrants->count();
            $half = intdiv($n, 2);
            $topHalf = $entrants->slice(0, $half)->values();
            $bottomHalf = $entrants->slice($half)->values();

            $pairs = [];
            for ($i = 0; $i < $half; $i++) {
                $pairs[] = [$topHalf[$i], $bottomHalf[$i]];
            }

            $byeEntrant = $bottomHalf->count() > $half ? $bottomHalf->last() : null;

            $this->createRound($division, 1, $pairs, $byeEntrant);

            $division->update(['status' => 'drawn']);
        });
    }

    /**
     * Pair the next Swiss round from current standings. Call once every match in the latest
     * round has finished. No-ops if the division has already reached its configured round count.
     */
    public function generateNextRound(TournamentDivision $division): void
    {
        $maxRound = Round::where('tournament_division_id', $division->id)->where('stage', 'swiss')->max('round_number') ?? 0;

        if ($maxRound === 0) {
            throw new RuntimeException('Primero debes generar la ronda 1.');
        }

        if ($division->swiss_rounds && $maxRound >= $division->swiss_rounds) {
            return;
        }

        DB::transaction(function () use ($division, $maxRound) {
            $standings = $this->standings->standingsForDivision($division);
            $entrantsById = $division->approvedEntrants()->get()->keyBy('id');

            $ordered = $standings
                ->map(fn (array $row) => $entrantsById->get($row['entrant_id']))
                ->filter()
                ->values();

            $playedPairs = $this->playedPairKeys($division);

            $queue = $ordered->all();
            $pairs = [];
            $byeEntrant = null;

            while (count($queue) > 0) {
                $a = array_shift($queue);

                if (count($queue) === 0) {
                    $byeEntrant = $a;
                    break;
                }

                $opponentIndex = null;
                foreach ($queue as $index => $candidate) {
                    if (! isset($playedPairs[$this->pairKey($a->id, $candidate->id)])) {
                        $opponentIndex = $index;
                        break;
                    }
                }

                $opponentIndex ??= 0;

                $b = $queue[$opponentIndex];
                array_splice($queue, $opponentIndex, 1);

                $pairs[] = [$a, $b];
                $playedPairs[$this->pairKey($a->id, $b->id)] = true;
            }

            $this->createRound($division, $maxRound + 1, $pairs, $byeEntrant);
        });
    }

    /**
     * @param  array<int, array{0: mixed, 1: mixed}>  $pairs
     */
    private function createRound(TournamentDivision $division, int $roundNumber, array $pairs, mixed $byeEntrant): void
    {
        $round = Round::create([
            'tournament_division_id' => $division->id,
            'stage' => 'swiss',
            'round_number' => $roundNumber,
            'name' => 'Ronda '.$roundNumber,
        ]);

        $matchNumber = 1;

        foreach ($pairs as [$a, $b]) {
            TournamentMatch::create([
                'tournament_division_id' => $division->id,
                'round_id' => $round->id,
                'match_number' => $matchNumber++,
                'entrant1_id' => $a->id,
                'entrant2_id' => $b->id,
                'status' => 'ready',
            ]);
        }

        if ($byeEntrant !== null) {
            TournamentMatch::create([
                'tournament_division_id' => $division->id,
                'round_id' => $round->id,
                'match_number' => $matchNumber,
                'entrant1_id' => $byeEntrant->id,
                'entrant2_is_bye' => true,
                'status' => 'completed',
                'winner_entrant_id' => $byeEntrant->id,
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * @return array<string, bool>
     */
    private function playedPairKeys(TournamentDivision $division): array
    {
        $matches = TournamentMatch::where('tournament_division_id', $division->id)
            ->where('round_id', '!=', null)
            ->whereHas('round', fn ($q) => $q->where('stage', 'swiss'))
            ->get(['entrant1_id', 'entrant2_id']);

        $keys = [];

        foreach ($matches as $match) {
            if ($match->entrant1_id && $match->entrant2_id) {
                $keys[$this->pairKey($match->entrant1_id, $match->entrant2_id)] = true;
            }
        }

        return $keys;
    }

    private function pairKey(int $a, int $b): string
    {
        return implode('-', [min($a, $b), max($a, $b)]);
    }
}
