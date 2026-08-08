<?php

namespace App\Services\Brackets;

use App\Models\TournamentDivision;
use RuntimeException;

class BracketGeneratorFactory
{
    public function make(TournamentDivision $division): BracketGenerator
    {
        return match ($division->format) {
            'single_elimination' => app(SingleEliminationGenerator::class),
            'double_elimination' => app(DoubleEliminationGenerator::class),
            'round_robin' => app(RoundRobinGenerator::class),
            'swiss' => app(SwissGenerator::class),
            'group_knockout' => app(GroupKnockoutGenerator::class),
            default => throw new RuntimeException("Formato de división desconocido: {$division->format}"),
        };
    }
}
