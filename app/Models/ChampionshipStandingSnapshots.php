<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['championship_id', 'matchday_id', 'captured_at', 'source', 'external_id'])]
class ChampionshipStandingSnapshots extends Model
{
    protected function casts(): array
    {
        return ['captured_at' => 'datetime'];
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function matchday(): BelongsTo
    {
        return $this->belongsTo(ChampionshipGamedays::class, 'matchday_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ChampionshipStandingEntries::class, 'standing_snapshot_id');
    }
}
