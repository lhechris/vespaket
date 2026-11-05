<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['matchday_id', 'home_team_id', 'away_team_id', 'scheduled_at', 'home_score', 'away_score', 'status', 'location', 'source', 'external_id'])]
class ChampionshipGames extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'home_score' => 'integer',
            'away_score' => 'integer',
        ];
    }

    public function matchday(): BelongsTo
    {
        return $this->belongsTo(ChampionshipGamedays::class, 'matchday_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(ChampionshipTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(ChampionshipTeam::class, 'away_team_id');
    }
}
