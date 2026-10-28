<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;
    
    const TYPE_PLAYER = 'player';
    const TYPE_COACH = 'coach';
    const TYPE_STAFF = 'staff';

    protected $fillable = [
        'name',
        'prenom',
        'type',
        'user_id',
        'licence',
        'numero'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('relation')
            ->withTimestamps();
    }


    public function parents()
    {
        return $this->users()->wherePivot('relation', 'parent');
    }

    
    public function teams()
    {
        return $this->belongsToMany(Team::class)
            ->withTimestamps();
    }


    public function trainings()
    {
        return $this->belongsToMany(Training::class);
    }


    public function games()
    {
        return $this->belongsToMany(Game::class)
            ->withPivot('availability', 'selected')
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
    public function isstaff() 
    {
        return $this->type === self::TYPE_STAFF;
    }
    public function iscoach() 
    {
        return $this->type === self::TYPE_COACH;
    }
}
