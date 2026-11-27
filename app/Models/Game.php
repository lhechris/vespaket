<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Game extends Model
{
    use HasFactory;
    
    protected $fillable = ['team_id','place_id', 'championship_game_id', 'date', 'rendezvous','numero','team_home','team_away','score_home','score_away','commentaire','whatsapp'];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function championshipGame()
    {
        return $this->belongsTo(ChampionshipGames::class, 'championship_game_id');
    }

    public function members()
    {
        return $this->belongsToMany(Member::class)
            ->withPivot('availability', 'selected','called')
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
        return  \Carbon\Carbon::parse($this->date)->translatedFormat('d F à H:i');
    }

    public function isDomicile() {
        return $this->team_home == $this->team->name;
    }

    public function score() {
        if ($this->score_home && $this->score_away) {
            return $this->score_home."/".$this->score_away;
        }
    }
    public function isVictory() {
        return (($this->isDomicile() && $this->score_home > $this->score_away)
                ||(!$this->isDomicile() && $this->score_home < $this->score_away));
    }

    public function isScheduled() {
        return \Carbon\Carbon::parse($this->date)->greaterThanOrEqualTo(now());
    }
    
}
