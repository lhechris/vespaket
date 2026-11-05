<?php

namespace App\Policies;

use App\Models\ChampionshipGamedays;
use App\Models\User;

class ChampionshipGamedaysPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ChampionshipGamedays $gameday): bool
    {
        return $user->isAdmin();
    }
}
