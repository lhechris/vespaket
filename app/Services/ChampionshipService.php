<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use App\Models\ChampionshipStandingSnapshots;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class ChampionshipService
{
    /**
     * Synchronise les matchs et les équipes d'un championnat Desimone.
     */
    public function refresh(Championship $championship): int
    {      
        if ($championship->source !== 'desimone') {
            throw new UnexpectedValueException('Seuls les championnats Desimone peuvent être synchronisés avec cette méthode.');
        }

        $pouleId = $championship->pool_external_id;
        $competitionId = $championship->external_id;

        if (! $pouleId) {
            throw new UnexpectedValueException("Le championnat ne contient pas d'identifiant de poule Desimone.");
        }

        $desimone = app(DesimoneService::class);
        $competition = $desimone->getCompetition($competitionId,$pouleId);
        
        $phases = collect($competition['phases'])
            ->flatMap(fn ($phase) => $phase['poules'])
            ->firstWhere('id', $pouleId);
        
        if ($phases) {
            $c = $this->refreshGames($championship, $pouleId, $phases['rencontres']);
            $this->refreshStanding($championship, $desimone->getClassement($pouleId));
            return $c;
        } else {
            \Log::info("Le champ phases n'existe pas dans competition ".print_r($competition,true));
        }
        return 0;
    }

    public function refreshAll() {
        
        $championships = Championship::all();
        foreach($championships as $championship) {
            $this->refresh($championship);
        }

    }

    private function refreshGames(Championship $championship, string|int $pouleId, array $matches): int
    {
        foreach ($matches as $match) {
            if (
                ! is_array($match)
                || ! isset(
                    $match['numeroJournee'],
                    $match['nomEquipe1'],
                    $match['nomEquipe2'],
                )
            ) {
                throw new UnexpectedValueException('Une rencontre Desimone ne contient pas les informations attendues.');
            }

            $roundNumber = filter_var($match['numeroJournee'], FILTER_VALIDATE_INT);
            if ($roundNumber === false || $roundNumber < 1 || $roundNumber > 65535) {
                throw new UnexpectedValueException('Une rencontre Desimone contient un numéro de journée invalide.');
            }            

            $matchday = ChampionshipGamedays::updateOrCreate(
                [
                    'championship_id' => $championship->id,
                    'number' => $roundNumber,
                ],
                [
                    'source' => 'desimone',
                    'external_id' => $pouleId.':'.$roundNumber,
                ]
            );

            $teams = [];
            foreach ([1, 2] as $teamNumber) {
                $teamName = trim((string) $match['nomEquipe'.$teamNumber]);
                if ($teamName === '') {
                    continue;
                }

                $teams[$teamNumber] = ChampionshipTeam::firstOrCreate(
                    [
                        'championship_id' => $championship->id,
                        'external_id' => $match['idEngagementEquipe'.$teamNumber]["id"],
                    ],
                    [
                        'name' => $teamName,
                        'source' => 'desimone',
                    ]
                );
            }

            if(count($teams)<2) {
                continue;
            }

            $score1 = is_numeric($match['resultatEquipe1'] ?? null)
                ? (int) $match['resultatEquipe1']
                : null;
            $score2 = is_numeric($match['resultatEquipe2'] ?? null)
                ? (int) $match['resultatEquipe2']
                : null;
            $externalId = (string) ($match['id'] ?? $match['numero'] ?? '');

            $location = "JSON:".json_encode($match["salle"]);

            if ($externalId === '') {
                throw new UnexpectedValueException('Une rencontre Desimone ne contient pas d’identifiant.');
            }

            ChampionshipGames::updateOrCreate(
                [
                    'source' => 'desimone',
                    'external_id' => $externalId,
                ],
                [
                    'matchday_id' => $matchday->id,
                    'home_team_id' => $teams[1]->id,
                    'away_team_id' => $teams[2]->id,
                    'scheduled_at' => ! empty($match['date_rencontre'])
                        ? Carbon::parse($match['date_rencontre'])
                        : null,
                    'home_score' => $score1,
                    'away_score' => $score2,
                    'status' => (int) ($match['joue'] ?? 0) === 1 && $score1 !== null && $score2 !== null
                        ? 'completed'
                        : 'scheduled',
                    'location' => $location,
                ]
            );
        }

        return count($matches);
    }

    public function refreshStanding(Championship $championship, array $classement,): ChampionshipStandingSnapshots
    {
        $entries = [];

        foreach ($classement as $row) {
            if (! is_array($row) || ! is_array($row['id_engagement'] ?? null) || empty($row['id_engagement']['nom'])) {
                continue;
            }

            $team = ChampionshipTeam::where([
                    'championship_id' => $championship->id,
                    'external_id' => $row['id_engagement']['id'],
                ])->firstOrFail();

            $entries[] = [
                'championship_team_id' => $team->id,
                'rank' => (int) ($row['position'] ?? 0),
                'played' => (int) ($row['match_joues'] ?? 0),
                'won' => (int) ($row['gagnes'] ?? 0),
                'lost' => (int) ($row['perdus'] ?? 0),
                'points_for' => (int) ($row['paniers_marques'] ?? 0),
                'points_against' => (int) ($row['paniers_encaisses'] ?? 0),
                'points' => (int) ($row['points'] ?? 0),
                'penalty_points' => (int) ($row['penalites_arbitrage'] ?? 0)
                    + (int) ($row['penalites_entraineur'] ?? 0)
                    + (int) ($row['penalites_diverses'] ?? 0),
            ];
        }

        $externalId = (string) ($championship->pool_external_id ?: $championship->external_id);
        $matchday = ChampionshipGamedays::query()
            ->where('championship_id', $championship->id)
            ->whereHas('games', fn ($query) => $query->where('status', 'completed'))
            ->orderByDesc('number')
            ->first();

        return DB::transaction(function () use ($championship, $entries, $externalId, $matchday) {
            $snapshot = ChampionshipStandingSnapshots::updateOrCreate(
                [
                    'championship_id' => $championship->id,
                    'source' => 'desimone',
                    'external_id' => $externalId,
                ],
                [
                    'matchday_id' => $matchday?->id,
                    'captured_at' => now(),
                ]
            );
            $snapshot->entries()->delete();
            $snapshot->entries()->createMany($entries);

            return $snapshot->load('entries');
        });
    }

    /**
     * Met à jour les scores des matchs depuis les championnats et inversement
     */
    public function refreshScores(): int
    {
        return DB::transaction(function (): int {
            $games = Game::query()
                ->whereNotNull('championship_game_id')
                ->with('championshipGame')
                ->get();
            $updated = 0;

            foreach ($games as $game) {
                $championshipGame = $game->championshipGame;
                if ($championshipGame === null) {
                    continue;
                }

                $gameHasScore = $game->score_home !== null && $game->score_away !== null;
                $gameHasNoScore = $game->score_home === null && $game->score_away === null;
                $championshipGameHasScore = $championshipGame->home_score !== null
                    && $championshipGame->away_score !== null;
                $championshipGameHasNoScore = $championshipGame->home_score === null
                    && $championshipGame->away_score === null;

                if ($gameHasScore && $championshipGameHasNoScore) {
                    $championshipGame->update([
                        'home_score' => $game->score_home,
                        'away_score' => $game->score_away,
                        'status' => 'completed',
                    ]);
                    $updated++;
                } elseif ($championshipGameHasScore && $gameHasNoScore) {
                    $game->update([
                        'score_home' => $championshipGame->home_score,
                        'score_away' => $championshipGame->away_score,
                    ]);
                    $updated++;
                }
            }

            return $updated;
        });
    }
}