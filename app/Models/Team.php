<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name','owner_id','whatsapp', 'msg_convocation'])]
class Team extends Model
{
    use HasFactory;
    
    public function owners()
    {
        return $this->belongsToMany(User::class, 'team_owner')
            ->withTimestamps();
    }

    public function owner()
    {
        return $this->owners()->first();
    }
    
    public function members() 
    {
        return $this->hasMany(Member::class);
    }

    public function games()
    {
        return $this->hasMany(\App\Models\Game::class)
                ->orderBy('date');
    }

    public function events()
    {
        return $this->hasMany(\App\Models\Event::class)
                ->orderBy('date');
    }

    public function trainings()
    {
        return $this->hasMany(\App\Models\Training::class)
                ->orderBy('date');
    }

    public function game_options() {
        return $this->hasMany(\App\Models\GameOption::class)
                ->orderBy('order');

    }

    // helpers utiles
    public function players()
    {
        return $this->members()->where('type', Member::TYPE_PLAYER);
    }

    public function coaches()
    {
        return $this->members()->where('type',Member::TYPE_COACH);
    }

    public function otms()
    {
        return $this->members()->where('type', Member::TYPE_OTM);
    }
    public function subs()
    {
        return $this->members()->where('type', Member::TYPE_SUB);
    }

    public function isU11()
    {
        $name = str_replace(" ","",strtolower($this->name));
        return (str_contains($name,"u11") || str_contains($name,"poussin"));
    }
}
