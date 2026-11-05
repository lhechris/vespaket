<?php

namespace App\Livewire\Championship\Gameday;

use App\Models\Championship;
use App\Models\Team;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public Championship $championship;

    public function mount(Team $team, Championship $championship): void
    {
        $this->team = $team;
        $this->championship = $championship;

        $this->authorize('TeamAccess', $this->team);
        abort_unless(
            $this->championship->teams()->where('team_id', $this->team->id)->exists(),
            404
        );
    }

    public function render()
    {
        return view('livewire.championship.gameday.index', [
            'gamedays' => $this->championship->gamedays()
                ->withCount('games')
                ->orderBy('number')
                ->get(),
        ]);
    }
}
