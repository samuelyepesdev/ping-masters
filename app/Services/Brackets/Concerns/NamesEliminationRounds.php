<?php

namespace App\Services\Brackets\Concerns;

trait NamesEliminationRounds
{
    private function eliminationRoundName(int $matchesInRound): string
    {
        return match ($matchesInRound) {
            1 => 'Final',
            2 => 'Semifinal',
            4 => 'Cuartos de final',
            8 => 'Octavos de final',
            16 => 'Dieciseisavos de final',
            default => 'Ronda de '.($matchesInRound * 2),
        };
    }
}
