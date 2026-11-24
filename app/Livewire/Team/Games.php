<?php

namespace App\Livewire\Team;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Team;

class Games extends Component
{
    public Team $team;
    public $games;
    public $nextGameId;

    public function mount()
    {
        $this->authorize('TeamAccess', $this->team);

        $this->loadGames();
    }

    #[On('games-imported')]
    public function refreshGames(): void
    {
        $this->authorize('TeamAccess', $this->team);
        $this->loadGames();
    }

    private function loadGames(): void
    {
        $this->nextGameId = $this->team->games()
            ->where('date', '>=', \Carbon\Carbon::now())
            ->orderBy('date')
            ->value('id');
        $this->games = $this->team->games()
            ->withCount(['members as members_count' => function ($query) {
                    $query->where('game_member.selected', 1);
            }])
            ->orderBy('date')
            ->get();
    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);
        
        return view('livewire.team.games');
    }
}
