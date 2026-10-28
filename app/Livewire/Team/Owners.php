<?php

namespace App\Livewire\Team;

use App\Models\Team;
use App\Models\User;
use Livewire\Component;

class Owners extends Component
{
    public Team $team;

    public $owners;

    public $search = '';

    public $results = [];

    public function mount(): void
    {
        $this->authorize('TeamAccess', $this->team);
    }

    public function updatedSearch()
    {
        $this->authorize('TeamAccess', $this->team);

        $this->results = User::where('firstname', 'like', "%{$this->search}%")
            ->limit(10)
            ->get();
    }

    public function addOwner($userId)
    {
        $this->authorize('TeamAccess', $this->team);

        $this->team->owners()->syncWithoutDetaching([
            $userId,
        ]);
    }

    public function removeOwner($userId)
    {
        $this->authorize('TeamAccess', $this->team);

        $this->team->owners()->detach($userId);
    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);

        $this->owners = $this->team->owners;

        return view('livewire.team.owners')->layout('layouts.app');
    }
}
