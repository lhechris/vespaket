<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['standing_snapshot_id', 'championship_team_id', 'rank', 'played', 'won', 'lost', 'points_for', 'points_against', 'points', 'penalty_points'])]
class ChampionshipStandingEntries extends Model
{
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(ChampionshipStandingSnapshots::class, 'standing_snapshot_id');
    }

    public function championshipTeam(): BelongsTo
    {
        return $this->belongsTo(ChampionshipTeam::class, 'championship_team_id');
    }
}
