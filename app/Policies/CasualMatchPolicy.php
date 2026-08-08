<?php

namespace App\Policies;

use App\Models\CasualMatch;
use App\Models\User;

class CasualMatchPolicy
{
    /**
     * View or score this reto: the creator, the joined opponent, or a super_admin.
     */
    public function score(User $user, CasualMatch $match): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $player = $user->player;

        if (! $player) {
            return false;
        }

        return $player->id === $match->creator_player_id || $player->id === $match->opponent_player_id;
    }
}
