<?php

namespace App\Livewire\Championship\Games;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
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

    #[Locked]
    public ChampionshipGames $game;

    public string $homeTeamId = '';

    public string $awayTeamId = '';

    public string $scheduledAt = '';

    public string $homeScore = '';

    public string $awayScore = '';

    public string $status = 'scheduled';

    public string $location = '';

    public function mount(
        Team $team,
        Championship $championship,
        ChampionshipGamedays $gameday,
        ChampionshipGames $game,
    ): void {
        $this->team = $team;
        $this->championship = $championship;
        $this->gameday = $gameday;
        $this->game = $game;

        $this->authorize('TeamAccess', $this->team);
        abort_unless(
            $this->championship->teams()->where('team_id', $this->team->id)->exists(),
            404
        );
        abort_unless($this->gameday->championship_id === $this->championship->id, 404);
        abort_unless($this->game->matchday_id === $this->gameday->id, 404);
        $this->authorize('update', $this->game);

        $this->homeTeamId = (string) $this->game->home_team_id;
        $this->awayTeamId = (string) $this->game->away_team_id;
        $this->scheduledAt = $this->game->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->homeScore = (string) $this->game->home_score;
        $this->awayScore = (string) $this->game->away_score;
        $this->status = $this->game->status;
        $this->location = $this->game->location ?? '';
    }

    public function save()
    {
        $this->authorize('TeamAccess', $this->team);
        $this->authorize('update', $this->game);

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

        $this->game->update([
            'home_team_id' => $validated['homeTeamId'],
            'away_team_id' => $validated['awayTeamId'],
            'scheduled_at' => $validated['scheduledAt'] ?: null,
            'home_score' => $validated['homeScore'] !== '' ? $validated['homeScore'] : null,
            'away_score' => $validated['awayScore'] !== '' ? $validated['awayScore'] : null,
            'status' => $validated['status'],
            'location' => $validated['location'] ?: null,
        ]);

        session()->flash('success', 'Match modifié.');

        return $this->redirectRoute('team.championship.gamedays.games.index', [
            'team' => $this->team,
            'championship' => $this->championship,
            'gameday' => $this->gameday,
        ]);
    }

    public function render()
    {
        return view('livewire.championship.games.edit', [
            'teams' => $this->championship->teams()->orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
