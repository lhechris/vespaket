<?php

namespace App\Livewire\Team;

use Livewire\Component;
use App\Models\Team;

class Selections extends Component
{
    public Team $team;
    public $members;
    public $games;

    public function mount()
    {
        $this->authorize('TeamAccess', $this->team);

        $this->loadData();
    }

    private function loadData() {
        $this->members = $this->team->players()            
            ->orderBy('name')
            ->withCount(['games as games_count' => function ($query) {
                $query->where('game_member.selected', 1);
            }])

            ->get();

        // Matchs de l'équipe (lignes), avec les pivots déjà chargés
        $this->games = $this->team->games()
            ->with(['members' => function ($query) {
                $query->where('type', \App\Models\Member::TYPE_PLAYER);
            }])
            ->withCount(['members as members_count' => function ($query) {
                $query->where('game_member.selected', 1);
            }])
            ->orderBy('date')
            ->get();

    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);

        return view('livewire.team.selections');
    }

    public function toggleSelection($gameId,$memberId)
    {
        $game = $this->games->find($gameId);

        $pivot = $game->members()->find($memberId)->pivot;

        $game->members()->updateExistingPivot($memberId, [
            'selected' => !$pivot->selected
        ]);
        $this->loadData();
    }
}
