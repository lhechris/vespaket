<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['championship_id', 'number', 'label', 'date', 'source', 'external_id'])]
class ChampionshipGamedays extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(ChampionshipGames::class, 'matchday_id');
    }
}
