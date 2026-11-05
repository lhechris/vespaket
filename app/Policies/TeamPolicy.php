<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function TeamAccess(User $user, Team $team): bool
    {
        return $user->isAdmin()
            || $team->owners()->whereKey($user->getKey())->exists();
    }

    public function TeamCreate(User $user): bool
    {
        \Log::debug("TeamCreate");
        return $user->isAdmin() || $user->isCoach();
    }
}
