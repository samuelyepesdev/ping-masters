<?php

namespace App\Services\Xp;

use App\Models\Level;

class LevelService
{
    public function levelForXp(int $xp): int
    {
        return Level::where('xp_required', '<=', $xp)->orderByDesc('level_number')->value('level_number') ?? 1;
    }
}
