<?php

namespace App\Livewire\Championship\Gameday;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\Team;
use App\Services\ChampionshipService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Create extends Component
{
    #[Locked]
    public Championship $championship;

    #[Locked]
    public Team $team;

    public string $number = '';
    public string $label = '';
    public string $date = '';

    public string $scorencoTeamId = '';
    public string $scorencoDayNumber = '';
    public string $scorencoDayLabel = '';

    public function mount(Team $team, Championship $championship): void
    {
        $this->championship = $championship;
        $this->team = $team;

        $this->authorize('TeamAccess', $this->team);
        $this->authorize('create', ChampionshipGamedays::class);
        abort_unless(
            $this->championship->teams()->where('team_id', $this->team->id)->exists(),
            404
        );
    }

    public function save()
    {
        $this->authorize('TeamAccess', $this->team);
        $this->authorize('create', ChampionshipGamedays::class);

        $validated = $this->validate([
            'number' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                \Illuminate\Validation\Rule::unique('championship_gamedays', 'number')
                    ->where('championship_id', $this->championship->id),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
        ]);

        $this->championship->gamedays()->create([
            'number' => $validated['number'],
            'label' => $validated['label'] ?: null,
            'date' => $validated['date'] ?: null,
            'source' => 'manual',
        ]);

        session()->flash('success', 'Journée créée.');

        return $this->redirectRoute('team.championship.edit', [
            'team' => $this->team,
            'championship' => $this->championship,
        ]);
    }

    public function refresh(ChampionshipService $srv): void
    {
        $this->authorize('TeamAccess', $this->team);
        $this->authorize('create', ChampionshipGamedays::class);

        $validated = $this->validate([
            'scorencoTeamId' => ['required', 'integer', 'min:1'],
        ]);
        $roundEvtId = (int) $validated['scorencoTeamId'];

        $importedCount = $srv->refreshGameday(
            $roundEvtId,
            $this->championship->id,
            $this->scorencoDayNumber !== '' ? (int) $this->scorencoDayNumber : null,
            $this->scorencoDayLabel !== '' ? $this->scorencoDayLabel : null,
        );

        $srv->refreshStanding($this->championship);

        session()->flash(
            'championship-refresh-success',
            $importedCount === 0
                ? 'Aucun match correspondant à cette équipe n’a été trouvé sur Scorenco.'
                : "{$importedCount} match(s) Scorenco synchronisé(s)."
        );
    }

    public function render()
    {
        return view('livewire.championship.gameday.create')
            ->layout('layouts.app');
    }
}
