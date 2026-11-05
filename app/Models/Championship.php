<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'season', 'category', 'division', 'pool', 'status', 'source', 'external_id'])]
class Championship extends Model
{
    public function teams(): HasMany
    {
        return $this->hasMany(ChampionshipTeam::class);
    }

    public function gamedays(): HasMany
    {
        return $this->hasMany(ChampionshipGamedays::class, 'championship_id')
            ->orderBy('number');
    }

    public function standingSnapshots(): HasMany
    {
        return $this->hasMany(ChampionshipStandingSnapshots::class);
    }
}
