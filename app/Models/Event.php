<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['titre','date','description','location','team_id'])]
class Event extends Model
{
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function members()
    {
        return $this->belongsToMany(Member::class)
            ->withPivot('availability','called')
            ->withTimestamps();
    }

    public function formatdate() {
        return  \Carbon\Carbon::parse($this->date)->translatedFormat('d F Y à H:i');
    }
}
