<?php

namespace App\Livewire\Championship;

use App\Models\Championship;
use App\Models\Team;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public Team $team;

    public function mount(Team $team): void
    {
        $this->team = $team;

        $this->authorize('TeamAccess', $this->team);
    }

    public function render()
    {
        $championships = Championship::query()
            ->whereHas('teams', fn ($query) => $query->where('team_id', $this->team->id))
            ->orderByDesc('season')
            ->orderByDesc('id')
            ->get();

        return view('livewire.championship.index', [
            'championships' => $championships,
        ])->layout('layouts.app');
    }
}
