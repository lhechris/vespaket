<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    use HasFactory;
    
    const TYPE_PLAYER = 'player';
    const TYPE_COACH = 'coach';
    const TYPE_OTM = 'otm';
    const TYPE_SUB = 'sub';

    protected $fillable = [
        'numero',
        'type',
        'licence',
        'team_id'
    ];

    public function licensee() : BelongsTo
    {
        return $this->belongsTo(Licensee::class);
    }    
  
    public function team() : BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function trainings()
    {
        return $this->belongsToMany(Training::class)
            ->withPivot('present')
            ->withTimestamps()
            ->orderBy('date');
    }

    public function games()
    {
        return $this->belongsToMany(Game::class)
            ->withPivot('availability', 'selected', 'called')
            ->withTimestamps()
            ->orderBy('date');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class)
            ->withPivot('availability')
            ->withTimestamps()
            ->orderBy('date');
    }

    public function gameOptions()
    {
        return $this->hasMany(GameMemberOption::class);
    }

    public function options()
    {
        return $this->belongsToMany(GameOption::class, 'game_member_option')
            ->withPivot('game_id', 'value')
            ->withTimestamps();
    }


    public function oppositionOptions()
    {
        return $this->gameOptions()->whereHas('option', function ($query) {
            $query->where('type', 'opposition');
        });
    }


    public function isplayer() 
    {
        return $this->type === self::TYPE_PLAYER;
    }
    public function isotm() 
    {
        return $this->type === self::TYPE_OTM;
    }
    public function iscoach() 
    {
        return $this->type === self::TYPE_COACH;
    }
    public function issub() 
    {
        return $this->type === self::TYPE_SUB;
    }    
}
