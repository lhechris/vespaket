<?php

namespace App\Policies;

use App\Models\Championship;
use App\Models\User;

class ChampionshipPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Championship $championship): bool
    {
        return $user->isAdmin();
    }
}
