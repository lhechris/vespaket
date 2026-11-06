<?php

namespace App\Policies;

use App\Models\ChampionshipGames;
use App\Models\User;

class ChampionshipGamesPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ChampionshipGames $game): bool
    {
        return $user->isAdmin();
    }
}
