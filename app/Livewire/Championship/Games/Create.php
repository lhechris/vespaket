<?php

namespace App\Livewire\Championship\Games;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\Team;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Create extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public Championship $championship;

    #[Locked]
    public ChampionshipGamedays $gameday;

    public string $homeTeamId = '';

    public string $awayTeamId = '';

    public string $scheduledAt = '';

    public string $homeScore = '';

    public string $awayScore = '';

    public string $status = 'scheduled';

    public string $location = '';

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
        $this->authorize('create', ChampionshipGames::class);
    }

    public function save()
    {
        $this->authorize('TeamAccess', $this->team);
        $this->authorize('create', ChampionshipGames::class);

        $validated = $this->validate([
            'homeTeamId' => [
                'required',
                'integer',
                Rule::exists('championship_teams', 'id')
                    ->where('championship_id', $this->championship->id),
                'different:awayTeamId',
            ],
            'awayTeamId' => [
                'required',
                'integer',
                Rule::exists('championship_teams', 'id')
                    ->where('championship_id', $this->championship->id),
            ],
            'scheduledAt' => ['nullable', 'date'],
            'homeScore' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'awayScore' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'postponed', 'cancelled'])],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $game = $this->gameday->games()->create([
            'home_team_id' => $validated['homeTeamId'],
            'away_team_id' => $validated['awayTeamId'],
            'scheduled_at' => $validated['scheduledAt'] ?: null,
            'home_score' => $validated['homeScore'] !== '' ? $validated['homeScore'] : null,
            'away_score' => $validated['awayScore'] !== '' ? $validated['awayScore'] : null,
            'status' => $validated['status'],
            'location' => $validated['location'] ?: null,
            'source' => 'manual',
        ]);

        session()->flash('success', 'Match créé.');

        return $this->redirectRoute('team.championship.gamedays.games.index', [
            'team' => $this->team,
            'championship' => $this->championship,
            'gameday' => $this->gameday,
        ]);
    }

    public function render()
    {
        return view('livewire.championship.games.create', [
            'teams' => $this->championship->teams()->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
