<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
    use HasFactory;
    
    protected $fillable = ['team_id','place_id', 'date', 'location','rendezvous','numero','titre','score','commentaire','whatsapp'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function members()
    {
        return $this->belongsToMany(Member::class)
            ->withPivot('availability', 'selected')
            ->withTimestamps();
    }

    public function memberOptions()
    {
        return $this->hasMany(GameMemberOption::class);
    }

    public function options()
    {
        return $this->belongsToMany(GameOption::class, 'game_member_option')
            ->withPivot('member_id', 'value')
            ->withTimestamps();
    }

    public function formatdate() {
        return  \Carbon\Carbon::parse($this->date)->translatedFormat('d F Y à H:i');
    }

    public function isDomicile() {
        return str_starts_with(strtolower($this->titre),'aslb');
    }
}
