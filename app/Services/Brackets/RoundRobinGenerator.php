<?php

namespace App\Services\Brackets;

use App\Models\Round;
use App\Models\TournamentDivision;
use App\Models\TournamentMatch;
use App\Services\Brackets\Concerns\BuildsRoundRobinSchedule;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoundRobinGenerator implements BracketGenerator
{
    use BuildsRoundRobinSchedule;

    public function __construct(private readonly SeedingService $seeding) {}

    public function generate(TournamentDivision $division): void
    {
        $entrants = $this->seeding->seededEntrants($division);

        if ($entrants->count() < 2) {
            throw new RuntimeException('Se necesitan al menos 2 inscritos aprobados para generar el sorteo.');
        }

        DB::transaction(function () use ($division, $entrants) {
            $schedule = $this->buildRoundRobinSchedule($entrants->all());

            foreach ($schedule as $roundIndex => $pairs) {
                $round = Round::create([
                    'tournament_division_id' => $division->id,
                    'stage' => 'group_stage',
                    'round_number' => $roundIndex + 1,
                    'name' => 'Ronda '.($roundIndex + 1),
                ]);

                $matchNumber = 1;

                foreach ($pairs as [$a, $b]) {
                    if ($a === null || $b === null) {
                        continue;
                    }

                    TournamentMatch::create([
                        'tournament_division_id' => $division->id,
                        'round_id' => $round->id,
                        'match_number' => $matchNumber++,
                        'entrant1_id' => $a->id,
                        'entrant2_id' => $b->id,
                        'status' => 'ready',
                    ]);
                }
            }

            $division->update(['status' => 'drawn']);
        });
    }
}
