<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Team;
use App\Models\Member;

class ManageMembers extends Component
{
    public Team $team;

    public $search = '';
    public $results = [];

    public function mount()
    {
        $this->authorize('TeamAccess', $this->team);
    }

    public function updatedSearch()
    {
        $this->results = Member::where('prenom', 'like', "%{$this->search}%")
            ->limit(10)
            ->get();
    }

    public function addMember($memberId)
    {
        $this->authorize('TeamAccess', $this->team);

        $this->team->members()->syncWithoutDetaching([
            $memberId
        ]);

        $now = \Carbon\Carbon::now();

        foreach ($this->team->games()->get() as $game) {
            if ($now->lt(\Carbon\Carbon::parse($game->date))) {
                $alreadyAttached = $game->members()->whereKey($memberId)->exists();

                if (! $alreadyAttached) {
                    \Log::info("attach $memberId dans le game $game->id");
                    $game->members()->attach($memberId);
                }
            }
        }

        foreach ($this->team->events()->get() as $event) {
            if ($now->lt(\Carbon\Carbon::parse($event->date))) {
                $alreadyAttached = $event->members()->whereKey($memberId)->exists();

                if (! $alreadyAttached) {
                    \Log::info("attach $memberId dans l'event $event->id");
                    $event->members()->attach($memberId);
                }
            }
        }
    }

    public function removeMember($memberId)
    {
        $this->authorize('TeamAccess', $this->team);

        $this->team->members()->detach($memberId);
        
        $now = \Carbon\Carbon::now();

        //Detach des games uniquement pour les future, on garde les anciens
        foreach($this->team->games()->get() as $game) {
            if ($now->lt(\Carbon\Carbon::parse($game->date))) {
                \Log::info("detach $memberId dans le game $game->id" );
                $game->members()->detach($memberId);
            }
        }        
        //Detach des events uniquement pour les future, on garde les anciens
        foreach($this->team->events()->get() as $event) {
            if ($now->lt(\Carbon\Carbon::parse($event->date))) {
                \Log::info("detach $memberId dans l'event $event->id" );
                $event->members()->detach($memberId);
            }
        }        


    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);
        
        return view('livewire.team.manage-members', [
            'members' => $this->team->members
        ])->layout('layouts.app');
    }
}