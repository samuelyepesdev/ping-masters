<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOrganizer() || $user->isSuperAdmin();
    }

    public function view(User $user, Tournament $tournament): bool
    {
        return $user->isSuperAdmin() || $tournament->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isSuperAdmin();
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $user->isSuperAdmin() || $tournament->created_by === $user->id;
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->isSuperAdmin() || $tournament->created_by === $user->id;
    }
}
