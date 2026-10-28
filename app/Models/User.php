<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'role','google_id','firstname'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasPushSubscriptions;

    const ROLE_ADMIN = 'admin';
    const ROLE_COACH = 'coach';
    const ROLE_PLAYER = 'player';
    const ROLE_PARENT = 'parent';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function teams()
    {
        return $this->belongsToMany(Team::class)
            ->withTimestamps();
    }

    public function ownedTeams()
    {
        return $this->belongsToMany(Team::class, 'team_owner')
            ->withTimestamps();
    }

    public function members()
    {
        return $this->belongsToMany(Member::class)
            ->withPivot('relation')
            ->withTimestamps();
    }
    
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function children()
    {
        return $this->members()->wherePivot('relation', 'parent');
    }


    public function isAdmin(): bool
    {
        return ($this->role === self::ROLE_ADMIN); 
    }

    public function isCoach(): bool
    {
        return (($this->role === self::ROLE_COACH) || ($this->isAdmin())); 
    }

    public function isPlayer(): bool
    {
        return $this->role === self::ROLE_PLAYER;
    }

    public function isParent(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

}
