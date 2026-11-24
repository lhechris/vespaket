<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use App\Models\Game;
use App\Models\Team;
use App\Models\Place;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnexpectedValueException;

class ChampionshipGameImportService
{
    public function import(
        Team $team,
        Championship $championship,
        bool $setTimeToMidnight = false,
        bool $importLocation = true,
    ): int {
        return DB::transaction(function () use ($team, $championship, $setTimeToMidnight, $importLocation): int {
            $championshipTeam = $this->findChampionshipTeam($team, $championship);
            $existingGames = Game::query()->where('team_id', $team->id)->get();
            $imported = 0;

            $championshipGames = ChampionshipGames::query()
                ->whereHas('matchday', fn ($query) => $query->where('championship_id', $championship->id))
                ->with(['homeTeam', 'awayTeam'])
                ->get();

            foreach ($championshipGames as $championshipGame) {
                $isHome = $championshipGame->home_team_id === $championshipTeam->id;
                $isAway = $championshipGame->away_team_id === $championshipTeam->id;

                if (! $isHome && ! $isAway) {
                    continue;
                }

                $opponent = $isHome
                    ? $championshipGame->awayTeam
                    : $championshipGame->homeTeam;
                $scheduledAt = $championshipGame->scheduled_at;

                if ($opponent === null) {
                    throw new UnexpectedValueException('Un match du championnat ne contient pas son équipe adverse.');
                }

                if ($scheduledAt === null) {
                    throw new UnexpectedValueException('Un match du championnat ne contient pas de date.');
                }

                if ($this->alreadyExists($existingGames, $scheduledAt->toDateString(), $opponent->name)) {
                    continue;
                }

                $homeTeam = $team->name;
                $awayTeam = $this->normalizeTeamName($championshipGame->awayTeam?->name);
                
                if ($isAway) {
                    $homeTeam = $this->normalizeTeamName($championshipGame->homeTeam?->name);
                    $awayTeam = $team->name;
                }

                $place = $importLocation ? $this->findGymnase($championshipGame) : null;

                $game = Game::query()->create([
                    'team_id' => $team->id,
                    'place_id' => $place?->id,
                    'team_home' => $homeTeam,
                    'team_away' => $awayTeam,
                    'date' => $setTimeToMidnight ? $scheduledAt->copy()->startOfDay() : $scheduledAt,
                    'score_home' => $championshipGame->home_score,
                    'score_away' => $championshipGame->away_score,
                ]);

                // initialiser tous les joueurs
                foreach ($team->players as $player) {
                    $game->members()->attach($player->id);
                }
                foreach ($team->otms as $otm) {
                    $game->members()->attach($otm->id);
                }
                foreach ($team->subs as $player) {
                    $game->members()->attach($player->id,["called"=>false]);
                }



                $existingGames->push($game);
                $imported++;
            }

            return $imported;
        });
    }

    private function findChampionshipTeam(Team $team, Championship $championship): ChampionshipTeam
    {
        $teams = $championship->teams()->get();
        $linkedTeam = $teams->firstWhere('team_id', $team->id);

        if ($linkedTeam !== null) {
            return $linkedTeam;
        }

        $unlinkedTeams = $teams->whereNull('team_id');
        $exactMatches = $unlinkedTeams->filter(
            fn (ChampionshipTeam $championshipTeam): bool => $this->normalizeName($championshipTeam->name) === $this->normalizeName($team->name)
        );

        if ($exactMatches->count() === 1) {
            return $exactMatches->first();
        }

        $matches = $unlinkedTeams->filter(
            fn (ChampionshipTeam $championshipTeam): bool => $this->sameTeamName($championshipTeam->name, $team->name)
        );

        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->isEmpty()) {
            throw new UnexpectedValueException(
                "L'équipe « {$team->name} » n'a pas été trouvée dans le championnat « {$championship->name} »."
            );
        }

        throw new UnexpectedValueException(
            "Plusieurs équipes du championnat correspondent à « {$team->name} »."
        );
    }

    /**
     * @param  Collection<int, Game>  $games
     */
    private function alreadyExists(Collection $games, string $date, string $opponentName): bool
    {
        return $games->contains(function (Game $game) use ($date, $opponentName): bool {
            if (substr((string) $game->date, 0, 10) !== $date) {
                return false;
            }
            return true;
        });
    }
    
    /**
     * 
     */
    private function findGymnase($game) {

        $place = null;
        if ($game->location && (substr($game->location,0,5) === 'JSON:')) {
            $j = json_decode(substr($game->location,5));

            $search = Str::ascii($j->libelle);
            $places = Place::where('cp', $j->commune->codePostal)
                ->get()
                ->filter(function ($place) use ($search) {
                    return str_contains(
                        strtolower(Str::ascii($place->label)),
                        strtolower($search)
                    );
                });

            $place = $places->first();

            if (!$place) {
                \Log::info("La salle n'a pas été trouvé :"
                            ." Libelle:".($j->libelle ?? '')
                            ." adresse:".($j->adresse ?? '')
                            ." cp:".($j->commune?->codePostal ?? '')
                            ." ville:".($j->commune?->libelle ?? '')
                            ." lat/long:".($j->cartographie?->latitude ?? '')."/".($j->cartographie?->longitude ?? ''));
            }

            //Si on a pas l'adresse on la met à jour
            if ($place && (!$place->address || ($place->address=="")) ) {
                $addr = Str::title(Str::lower($j->adresse));
                \Log::info("L'adresse de la salle ".$place->label.", ".$place->city." a ete mise à jour ".$addr);
                $place->update(["address" => $addr]);
            }
                

        }
        return $place;
    }



    private function sameTeamName(string $first, string $second): bool
    {
        $firstTokens = array_values(array_filter(
            array_unique(explode(' ', $this->normalizeName($first))),
            fn (string $token): bool => $token !== ''
        ));
        $secondTokens = array_values(array_filter(
            array_unique(explode(' ', $this->normalizeName($second))),
            fn (string $token): bool => $token !== ''
        ));

        if ($firstTokens === [] || $secondTokens === []) {
            return false;
        }

        $commonTokens = count(array_intersect($firstTokens, $secondTokens));
        $smallestTokenCount = min(count($firstTokens), count($secondTokens));
        $largestTokenCount = max(count($firstTokens), count($secondTokens));

        return $commonTokens / $smallestTokenCount >= 0.8
            && $commonTokens / $largestTokenCount >= 0.6;
    }

    private function normalizeName(string $name): string
    {
        $name = Str::ascii(mb_strtolower(trim($name)));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $name) ?? '');
    }

    private function normalizeTeamName(string $name): string
    {
        // Remplace les tirets par des espaces
        $name = str_replace('-', ' ', trim($name));

        // Supprime les espaces multiples
        $name = preg_replace('/\s+/', ' ', $name);

        // Mise en forme standard
        $name = mb_convert_case(
            mb_strtolower($name, 'UTF-8'),
            MB_CASE_TITLE,
            'UTF-8'
        );

        // Exceptions
        $exceptions = [
            'Ie'    => 'IE',
            'Ctc'   => 'CTC',
            'Un. Ol.'=> 'UNO',
            'As'    => 'AS',
            'Bc'    => 'Bc',
            'B.C'   => 'BC',
            'B.C.'  => 'BC',
            'U.S'   => 'US',
            'U.s.'  => 'US',
            'Us'    => 'US',
            'Otb'   => 'OTB',
        ];

        return str_replace(
            array_keys($exceptions),
            array_values($exceptions),
            $name
        );
    }
}
