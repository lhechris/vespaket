<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class ScorencoService
{
    private const URL = 'https://graphql.scorenco.com/v1/graphql';
    private const URLSEARCH = 'https://search.scorenco.com/search';


    /**
     * Retourne la liste des matchs d'une équipe donnée
     */
    public function getMatchesByTeamId(
        int $teamId,
        int $dateFilter = -1,
        int $limit = 10,
        int $offset = 0
    ): array {
        $query = <<<'GRAPHQL'
query GetMatchs(
    $teamId: Int,
    $dateFilter: Int,
    $offset: Int = 0,
    $limit: Int
) {
    competitions_event_detail_by_team_id(
        args: {
            date_filter: $dateFilter,
            id: $teamId
        }
        limit: $limit
        offset: $offset
    ) {
        id
        status
        date
        time
        level_name
        competition_id
        url

        sport {
            id
            slug
        }

        teams
    }
}
GRAPHQL;

        $response = Http::asJson()->post(self::URL, [
            'operationName' => 'GetMatchs',
            'variables' => [
                'teamId' => $teamId,
                'dateFilter' => $dateFilter,
                'offset' => $offset,
                'limit' => $limit,
            ],
            'query' => $query,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Erreur Scorenco : {$response->body()}"
            );
        }

        return $response->json(
            'data.competitions_event_detail_by_team_id',
            []
        );
    }

    /**
     * Retourne la liste des matchs d'une journée d'un championnat
     */
    public function getRoundEvents(int $roundId): array
    {
        $query = <<<'GRAPHQL'
    query EventsByRoundId($roundId: Int!) {
    competitions_event_detail_by_round_day_id(
        args: {
        round_day_id: $roundId
        }
    ) {
        id
        status
        time
        date
        level_name

        place {
        id
        name
        }

        teams

        url
    }
    }
    GRAPHQL;

        $response = Http::asJson()->post(self::URL, [
            'operationName' => 'EventsByRoundId',
            'variables' => [
                'roundId' => $roundId,
            ],
            'query' => $query,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("Erreur Scorenco : {$response->body()}");
        }

        $matches = $response->json('data.competitions_event_detail_by_round_day_id');
        if (! is_array($matches)) {
            throw new \RuntimeException('La réponse Scorenco ne contient pas les matchs de la journée.');
        }

        return $matches;
    }

    /**
     * Retourne l'identifiant d'un club par son nom
     */
    public function getClubId(string $search) : string {
        $response = Http::asJson()->post(self::URLSEARCH, [
            'sport_slug' => 'basket',
            'term' => $search,
            'type' => 'club',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("Erreur Scorenco : {$response->body()}");
        }

        $clubs = $response->json();
        
        if (! is_array($clubs)) {
            throw new \RuntimeException('La réponse Scorenco ne contient pas de liste de club.');
        }

        if (!key_exists('hits',$clubs)) {
            throw new \RuntimeException("La réponse Scorenco ne contient pas l'attendu");
        }

        foreach($clubs["hits"] as $club) {
            if (key_exists("slug",$club) && key_exists("name",$club)) {
                $name = str_replace(' ','',strtolower($club["name"]));
                if (  ($club["slug"]=="aslb-labarthe-sur-leze") 
                    ||($name=="aslblabarthesurlèze")
                    ||($name=="aslblabarthesurleze")) {
                        return $club["id"];
                    }
            }
        }

        return null;
        
    }


}
