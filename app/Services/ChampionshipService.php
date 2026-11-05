<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipStandingEntries;
use App\Models\ChampionshipStandingSnapshots;
use App\Models\ChampionshipTeam;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class ChampionshipService
{
    /**
     * Synchronise une journée Scorenco et ses matchs dans le championnat.
     */
    public function refreshGameday(int $roundEventId, int $championshipId, ?int $daynumber, ?string $label): int
    {

        $sc = app(\App\Services\ScorencoService::class);

        $matches = $sc->getRoundEvents($roundEventId);

        if ($matches === []) {
            return 0;
        }

        return DB::transaction(function () use ($roundEventId, $championshipId, $matches, $daynumber, $label): int {
            $championship = Championship::query()->findOrFail($championshipId);
            $source = 'scorenco';
            $roundExternalId = (string) $roundEventId;
            $normalizedMatches = [];

            foreach ($matches as $match) {
                if (! is_array($match) || ! isset($match['id'])) {
                    throw new UnexpectedValueException('La réponse Scorenco contient un match sans identifiant.');
                }

                $teams = collect($match['teams'] ?? []);
                $home = $teams->firstWhere('role', 'home');
                $away = $teams->firstWhere('role', 'away');
                if (! is_array($home) || ! is_array($away)) {
                    throw new UnexpectedValueException('Un match Scorenco ne contient pas ses deux équipes.');
                }

                $date = $match['date'] ?? $match['time'] ?? null;
                if (! $date) {
                    throw new UnexpectedValueException('Un match Scorenco ne contient pas de date.');
                }

                $normalizedMatches[] = [
                    'id' => (string) $match['id'],
                    'date' => Carbon::parse($date)->toDateString(),
                    'scheduled_at' => Carbon::parse($match['time'] ?? $date),
                    'home' => $home,
                    'away' => $away,
                    'status' => $this->gameStatus((string) ($match['status'] ?? '')),
                    'location' => data_get($match, 'place.name'),
                ];
            }

            $date = collect($normalizedMatches)->min('date');
            $matchday = $championship->gamedays()
                ->where('source', $source)
                ->where('external_id', $roundExternalId)
                ->first();

            if (! $matchday) {
                $matchday = $championship->gamedays()
                    ->where('source', $source)
                    ->whereDate('date', $date)
                    ->first();
            }

            if (! $matchday) {
                $number = $daynumber ? $daynumber : ((int) $championship->gamedays()->max('number')) + 1;
                $matchday = new ChampionshipGamedays([
                    'championship_id' => $championship->id,
                    'number' => $number,
                    'label' => $label,
                ]);
            }

            $matchday->fill([
                'date' => $date,
                'source' => $source,
                'external_id' => $roundExternalId,
            ]);
            $matchday->save();

            foreach ($normalizedMatches as $match) {
                $homeTeam = $this->syncChampionshipTeam($championship, $match['home']);
                $awayTeam = $this->syncChampionshipTeam($championship, $match['away']);

                ChampionshipGames::query()->updateOrCreate(
                    ['source' => $source, 'external_id' => $match['id']],
                    [
                        'matchday_id' => $matchday->id,
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'scheduled_at' => $match['scheduled_at'],
                        'home_score' => $this->score($match['home']),
                        'away_score' => $this->score($match['away']),
                        'status' => $match['status'],
                        'location' => $match['location'],
                    ]
                );
            }

            return count($normalizedMatches);
        });
    }

    /**
     * Met à jour le championnat
     */
    public function refreshStanding(Championship $championship): ChampionshipStandingSnapshots
    {
        return DB::transaction(function () use ($championship): ChampionshipStandingSnapshots {
            $championship = Championship::query()->findOrFail($championship->id);
            $teams = $championship->teams()->orderBy('id')->get();
            $standings = [];

            foreach ($teams as $team) {
                $standings[$team->id] = [
                    'championship_team_id' => $team->id,
                    'played' => 0,
                    'won' => 0,
                    'lost' => 0,
                    'points_for' => 0,
                    'points_against' => 0,
                    'points' => 0,
                ];
            }

            $games = ChampionshipGames::query()
                ->whereHas('matchday', fn ($query) => $query->where('championship_id', $championship->id))
                ->where('status', 'completed')
                ->whereNotNull('home_score')
                ->whereNotNull('away_score')
                ->with('matchday')
                ->get();

            foreach ($games as $game) {
                if (! isset($standings[$game->home_team_id], $standings[$game->away_team_id])) {
                    continue;
                }

                $home = &$standings[$game->home_team_id];
                $away = &$standings[$game->away_team_id];
                $home['played']++;
                $away['played']++;
                $home['points_for'] += $game->home_score;
                $home['points_against'] += $game->away_score;
                $away['points_for'] += $game->away_score;
                $away['points_against'] += $game->home_score;

                if ($game->home_score > $game->away_score) {
                    $home['won']++;
                    $away['lost']++;
                    $home['points'] += 2;
                    $away['points']++;
                } elseif ($game->away_score > $game->home_score) {
                    $away['won']++;
                    $home['lost']++;
                    $away['points'] += 2;
                    $home['points']++;
                }

                unset($home, $away);
            }

            $ranking = collect($standings)
                ->map(fn (array $standing): array => [
                    ...$standing,
                    'difference' => $standing['points_for'] - $standing['points_against'],
                    'team_name' => $teams->firstWhere('id', $standing['championship_team_id'])->name,
                ])
                ->sortBy([
                    ['points', 'desc'],
                    ['difference', 'desc'],
                    ['points_for', 'desc'],
                    ['team_name', 'asc'],
                    ['championship_team_id', 'asc'],
                ])
                ->values();

            $latestMatchday = $games
                ->sortBy(fn (ChampionshipGames $game): array => [
                    $game->matchday?->number ?? 0,
                    $game->scheduled_at?->timestamp ?? 0,
                    $game->id,
                ])
                ->last()
                ?->matchday;

            $snapshot = ChampionshipStandingSnapshots::query()->updateOrCreate(
                [
                    'source' => 'calculated',
                    'external_id' => 'championship-'.$championship->id,
                ],
                [
                    'championship_id' => $championship->id,
                    'matchday_id' => $latestMatchday?->id,
                    'captured_at' => now(),
                ]
            );
            $snapshot->entries()->delete();

            foreach ($ranking as $index => $standing) {
                ChampionshipStandingEntries::create([
                    'standing_snapshot_id' => $snapshot->id,
                    'championship_team_id' => $standing['championship_team_id'],
                    'rank' => $index + 1,
                    'played' => $standing['played'],
                    'won' => $standing['won'],
                    'lost' => $standing['lost'],
                    'points_for' => $standing['points_for'],
                    'points_against' => $standing['points_against'],
                    'points' => $standing['points'],
                    'penalty_points' => 0,
                ]);
            }

            return $snapshot;
        });
    }

    /**
     * @param  array<string, mixed>  $scorencoTeam
     */
    private function syncChampionshipTeam(Championship $championship, array $scorencoTeam): ChampionshipTeam
    {
        $externalId = $scorencoTeam['team_id']
            ?? $scorencoTeam['id']
            ?? null;
        $name = $scorencoTeam['name_in_competition']
            ?? $scorencoTeam['name_in_club']
            ?? null;

        if (! $externalId || ! $name) {
            throw new UnexpectedValueException('Une équipe Scorenco ne contient pas son identifiant ou son nom.');
        }

        return ChampionshipTeam::query()->updateOrCreate(
            [
                'championship_id' => $championship->id,
                'source' => 'scorenco',
                'external_id' => (string) $externalId,
            ],
            [
                'name' => $name,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $team
     */
    private function score(array $team): ?int
    {
        $score = $team['score'] ?? null;

        if ($score === null || $score === '') {
            return null;
        }

        if (! is_numeric($score) || (int) $score < 0 || (int) $score > 65535) {
            throw new UnexpectedValueException('Un score Scorenco est invalide.');
        }

        return (int) $score;
    }

    private function gameStatus(string $status): string
    {
        return match (strtolower($status)) {
            'finish', 'finished', 'complete', 'completed' => 'completed',
            'cancel', 'cancelled', 'canceled' => 'cancelled',
            default => 'scheduled',
        };
    }
}
