<?php

namespace App\Livewire\Team;

use App\Models\Championship;
use App\Models\Team;
use App\Services\ChampionshipGameImportService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ImportGames extends Component
{
    #[Locked]
    public Team $team;

    public bool $showForm = false;

    public string $selectedChampionshipId = '';

    public bool $setTimeToMidnight = false;

    public bool $importLocation = true;

    public ?string $feedback = null;

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->authorize('TeamAccess', $this->team);
    }

    public function toggleForm(): void
    {
        $this->authorize('TeamAccess', $this->team);
        $this->showForm = ! $this->showForm;
        $this->feedback = null;
    }

    public function importGames(ChampionshipGameImportService $importer): void
    {
        $this->authorize('TeamAccess', $this->team);

        $validated = $this->validate([
            'selectedChampionshipId' => ['required', 'integer'],
            'setTimeToMidnight' => ['boolean'],
            'importLocation' => ['boolean'],
        ]);

        $championship = $this->availableChampionships()
            ->find($validated['selectedChampionshipId']);

        if ($championship === null) {
            throw ValidationException::withMessages([
                'selectedChampionshipId' => 'Le championnat sélectionné n’est pas associé à cette équipe.',
            ]);
        }

        $imported = $importer->import(
            $this->team,
            $championship,
            $validated['setTimeToMidnight'],
            $validated['importLocation'],
        );

        $this->feedback = $imported === 0
            ? 'Aucun nouveau match à importer.'
            : $imported.' match'.($imported > 1 ? 's' : '').' importé'.($imported > 1 ? 's' : '').'.';

        $this->dispatch('games-imported');
    }

    private function availableChampionships()
    {
        return Championship::query()
            ->whereHas('teams', fn ($query) => $query->where('team_id', $this->team->id))
            ->orderByDesc('season')
            ->orderBy('name');
    }

    public function render()
    {
        $this->authorize('TeamAccess', $this->team);

        return view('livewire.team.import-games', [
            'championships' => $this->availableChampionships()->get(),
        ]);
    }
}
