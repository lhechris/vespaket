<?php

namespace App\Livewire\Championship;

use App\Models\Championship;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Manage extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public ?Championship $championship = null;

    public string $name = '';
    public string $season = '';
    public string $category = '';
    public string $division = '';
    public string $pool = '';
    public string $scorencoteamid = '';

    public function mount(Team $team, ?Championship $championship = null): void
    {
        $this->team = $team;

        $this->authorize('TeamAccess', $this->team);

        if ($championship) {
            $championshipTeam = $championship->teams()
                ->where('team_id', $this->team->id)
                ->first();
            abort_unless($championshipTeam, 404);
            $this->authorize('update', $championship);

            $this->championship = $championship;
            $this->name = $championship->name;
            $this->season = $championship->season;
            $this->category = $championship->category ?? '';
            $this->division = $championship->division ?? '';
            $this->pool = $championship->pool ?? '';
            $this->scorencoteamid = $championshipTeam->external_id ?? '';
        } else {
            $this->authorize('create', Championship::class);
        }
    }

    public function save()
    {
        $this->authorize('TeamAccess', $this->team);
        $this->authorize(
            $this->championship ? 'update' : 'create',
            $this->championship ?? Championship::class
        );

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'season' => ['required', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:255'],
            'division' => ['nullable', 'string', 'max:255'],
            'pool' => ['nullable', 'string', 'max:255'],
            'scorencoteamid' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated): void {
            $attributes = [
                'name' => $validated['name'],
                'season' => $validated['season'],
                'category' => $validated['category'] ?: null,
                'division' => $validated['division'] ?: null,
                'pool' => $validated['pool'] ?: null,
            ];

            if ($this->championship) {
                $this->championship->update($attributes);
                $this->championship->teams()
                    ->where('team_id', $this->team->id)
                    ->firstOrFail()
                    ->update([
                        'name' => $this->team->name,
                        'source' => 'scorenco',
                        'external_id' => $validated['scorencoteamid'],
                    ]);

                return;
            }

            $championship = Championship::create([
                ...$attributes,
                'status' => 'active',
                'source' => 'manual',
            ]);

            $championship->teams()->create([
                'team_id' => $this->team->id,
                'name' => $this->team->name,
                'source' => 'scorenco',
                'external_id' => $validated['scorencoteamid'],
            ]);
        });

        session()->flash('success', $this->championship ? 'Championnat modifié.' : 'Championnat créé.');

        return $this->redirectRoute('championship.show', ['team' => $this->team]);
    }

    public function render()
    {
        return view('livewire.championship.manage')
            ->layout('layouts.app');
    }
}
