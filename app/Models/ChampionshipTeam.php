<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['championship_id', 'team_id', 'name', 'source', 'external_id'])]
class ChampionshipTeam extends Model
{
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function standingEntries(): HasMany
    {
        return $this->hasMany(ChampionshipStandingEntries::class);
    }
}
