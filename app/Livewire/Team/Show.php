<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Team;

class Show extends Component
{
    public Team $team;
    public string $activeTab = 'games';

    public function mount(): void
    {
        $this->authorize('TeamAccess', $this->team);
    }


    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);

        return view('livewire.team.show')->layout('layouts.app');
    }
}
