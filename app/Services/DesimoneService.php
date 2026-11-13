<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class DesimoneService
{
    private const URL = 'https://ffbb-api.desimone.fr/api/v1';


    /**
     * Retourne le numéro du club, nécessaire pour chaque requête
     */
    public function getClubId($search) {

        $response = Http::get(self::URL."/search?query=".urlencode($search));

        if ($response->failed()) {
            throw new \RuntimeException(
                "Erreur Desimone : {$response->body()}"
            );
        }

        $j = $response->json();
        foreach ($j as $c) {
            if (array_key_exists("index_uid",$c) && (array_key_exists("hits",$c))) {
                if ($c["index_uid"] == "ffbbserver_organismes") {
                    foreach($c["hits"] as $h) {
                        if(array_key_exists("id",$h)) {
                            return $h["id"];
                        }
                    }
                }
            }
        }

        return null;
    }


    /**
     * Retourne le numéro du club, nécessaire pour chaque requête
     */
    public function getTeams(string|int $clubId): array
    {
        $response = Http::get(self::URL.'/club/'.urlencode((string) $clubId).'/teams');

        if ($response->failed()) {
            throw new \RuntimeException("Erreur Desimone : {$response->body()}");
        }

        $club = $response->json();

        if (! is_array($club) || ! is_array($club['teams'] ?? null)) {
            throw new UnexpectedValueException('La réponse Desimone ne contient pas les équipes du club.');
        }

        return $club['teams'];
    }


    /**
     * Retourne les info d'une poule
     */
    public function getPoule(string|int $pouleId): array
    {
        $response = Http::get(self::URL.'/poule/'.urlencode((string) $pouleId));

        if ($response->failed()) {
            throw new \RuntimeException("Erreur Desimone : {$response->body()}");
        }

        $poule = $response->json();

        if (! is_array($poule) || ! is_array($poule['rencontres'] ?? null)) {
            throw new UnexpectedValueException('La réponse Desimone ne contient pas les rencontres de la poule.');
        }

        return $poule;
    }

    /**
     * Retourne les info d'une competition
     */
    public function getCompetition(string|int $competitionId, string|int $pouleId): array
    {
        $response = Http::get(self::URL.'/competition/'.urlencode((string) $competitionId));

        if ($response->failed()) {
            throw new \RuntimeException("Erreur Desimone : {$response->body()}");
        }

        $competition = $response->json();

        return $competition;
    }

    /**
     * Retourne le numéro du club, nécessaire pour chaque requête
     */
    public function getClassement(string|int $pouleId): array
    {
        $response = Http::get(self::URL.'/poule/'.urlencode((string) $pouleId).'/classement');

        if ($response->failed()) {
            throw new \RuntimeException("Erreur Desimone : {$response->body()}");
        }

        $ranking = $response->json('classement');

        if (! is_array($ranking)) {
            throw new UnexpectedValueException('La réponse Desimone ne contient pas le classement de la poule.');
        }

        return $ranking;
    }    


}