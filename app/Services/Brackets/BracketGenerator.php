<?php

namespace App\Services\Brackets;

use App\Models\TournamentDivision;

interface BracketGenerator
{
    public function generate(TournamentDivision $division): void;
}
