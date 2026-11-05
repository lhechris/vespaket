<?php

namespace App\Livewire\Championship;

use App\Models\Championship as ChampionshipModel;
use App\Models\ChampionshipGamedays;
use App\Models\Team as TeamModel;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;

use App\Services\ChampionshipService;

class Show extends Component
{
    #[Locked]
    public TeamModel $team;

    public ?int $championshipId = null;

    public ?int $selectedMatchdayId = null;

    public function mount(TeamModel $team): void
    {
        $this->team = $team;

        $championship = $this->championships()
            ->orderByDesc('season')
            ->orderByDesc('id')
            ->first();

        if ($championship) {
            $this->championshipId = $championship->id;
            $this->selectedMatchdayId = $this->defaultMatchday($championship)?->id;
        }
    }

    public function selectChampionship(int $championshipId): void
    {
        $championship = $this->championships()->findOrFail($championshipId);

        $this->championshipId = $championship->id;
        $this->selectedMatchdayId = $this->defaultMatchday($championship)?->id;
    }

    public function selectMatchday(int $matchdayId): void
    {
        abort_unless($this->championshipId, 404);

        $this->selectedMatchdayId = ChampionshipGamedays::query()
            ->where('championship_id', $this->championshipId)
            ->findOrFail($matchdayId)
            ->id;
    }

    private function championships(): Builder
    {
        return ChampionshipModel::query()
            ->whereHas('teams', fn (Builder $query) => $query->where('team_id', $this->team->id));
    }

    private function defaultMatchday(ChampionshipModel $championship): ?ChampionshipGamedays
    {
        $matchdays = $championship->gamedays()
            ->with('games')
            ->orderBy('number')
            ->get();

        foreach($matchdays as $m) {
            \Log::debug("$m->id $m->number $m->date");
        }

        return $matchdays->first(function (ChampionshipGamedays $matchday): bool {
            return $matchday->games->contains(
                fn ($game): bool => $game->scheduled_at?->isFuture() && $game->status !== 'completed'
            );
        }) ?? $matchdays->filter(fn (ChampionshipGamedays $matchday): bool => $matchday->games->isNotEmpty())
            ->last()
            ?? $matchdays->last();
    }

    public function refreshMatchday(ChampionshipService $srv, $id) {
        $matchday = ChampionshipGamedays::query()
            ->where('championship_id', $this->championshipId)
            ->findOrFail($id);
        $this->authorize('update', $matchday);
        $championship = ChampionshipModel::findOrFail($this->championshipId);
        $srv->refreshGameday($matchday->external_id,$this->championshipId,null,null);
        $srv->refreshStanding($championship);
    }

    public function render()
    {
        $championships = $this->championships()
            ->orderByDesc('season')
            ->orderByDesc('id')
            ->get();
        $championship = $this->championshipId
            ? $championships->firstWhere('id', $this->championshipId)
            : null;
        $matchdays = $championship
            ? $championship->gamedays()->with(['games.homeTeam', 'games.awayTeam'])->get()
            : collect();
        $matchday = $matchdays->firstWhere('id', $this->selectedMatchdayId);
        $snapshot = $championship?->standingSnapshots()
            ->with(['entries.championshipTeam'])
            ->orderByDesc('captured_at')
            ->orderByDesc('id')
            ->first();
        $standings = $snapshot?->entries->sortBy('rank')->values() ?? collect();

        return view('livewire.championship.show', [
            'championships' => $championships,
            'championship' => $championship,
            'matchdays' => $matchdays,
            'matchday' => $matchday,
            'snapshot' => $snapshot,
            'standings' => $standings,
        ])->layout('layouts.app');
    }
}
