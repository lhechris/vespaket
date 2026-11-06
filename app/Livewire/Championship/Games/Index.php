<?php

namespace App\Livewire\Championship\Games;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\Team;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public Championship $championship;

    #[Locked]
    public ChampionshipGamedays $gameday;

    public function mount(Team $team, Championship $championship, ChampionshipGamedays $gameday): void
    {
        $this->team = $team;
        $this->championship = $championship;
        $this->gameday = $gameday;

        $this->authorize('TeamAccess', $this->team);
        abort_unless(
            $this->championship->teams()->where('team_id', $this->team->id)->exists(),
            404
        );
        abort_unless($this->gameday->championship_id === $this->championship->id, 404);
    }

    public function render()
    {
        return view('livewire.championship.games.index', [
            'games' => $this->gameday->games()
                ->with(['homeTeam', 'awayTeam'])
                ->orderBy('scheduled_at')
                ->orderBy('id')
                ->get(),
        ])->layout('layouts.app');
    }
}
