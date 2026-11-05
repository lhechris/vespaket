<?php

namespace App\Livewire\Championship\Gameday;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\Team;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Edit extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public Championship $championship;

    #[Locked]
    public ChampionshipGamedays $gameday;

    public string $number = '';
    public string $label = '';
    public string $date = '';
    public string $external_id = '';

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
        $this->authorize('update', $this->gameday);

        $this->number = (string) $this->gameday->number;
        $this->label = $this->gameday->label ?? '';
        $this->date = $this->gameday->date?->format('Y-m-d') ?? '';
        $this->external_id = (string) $this->gameday->external_id;
    }

    public function save()
    {
        $this->authorize('TeamAccess', $this->team);
        $this->authorize('update', $this->gameday);

        $validated = $this->validate([
            'number' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                Rule::unique('championship_gamedays', 'number')
                    ->where('championship_id', $this->championship->id)
                    ->ignore($this->gameday->id),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'external_id' => ['nullable']
        ]);

        $this->gameday->update([
            'number' => $validated['number'],
            'label' => $validated['label'] ?: null,
            'date' => $validated['date'] ?: null,
            'external_id' => $validated['external_id'] ?: null,
        ]);

        session()->flash('success', 'Journée modifiée.');

        return $this->redirectRoute('team.championship.edit', [
            'team' => $this->team,
            'championship' => $this->championship,
        ]);
    }

    public function render()
    {
        return view('livewire.championship.gameday.edit')
            ->layout('layouts.app');
    }
}
