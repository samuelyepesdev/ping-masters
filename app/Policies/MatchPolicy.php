<?php

namespace App\Policies;

use App\Models\TournamentMatch;
use App\Models\User;

class MatchPolicy
{
    /**
     * Score this match: the tournament's organizer/super_admin always can, or the referee
     * specifically assigned to this match.
     */
    public function score(User $user, TournamentMatch $match): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($match->division->tournament->created_by === $user->id) {
            return true;
        }

        return $match->referee_id !== null && $match->referee_id === $user->id;
    }

    /**
     * Assign or change who referees this match: organizer/super_admin only.
     */
    public function assignReferee(User $user, TournamentMatch $match): bool
    {
        return $user->isSuperAdmin() || $match->division->tournament->created_by === $user->id;
    }
}
