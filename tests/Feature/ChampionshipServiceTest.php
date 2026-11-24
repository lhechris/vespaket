<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use App\Services\ChampionshipService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChampionshipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_updates_the_desimone_ranking(): void
    {
        $championship = Championship::create([
            'name' => 'Championnat Desimone',
            'season' => '2026-2027',
            'source' => 'desimone',
            'external_id' => 'competition-1',
            'pool_external_id' => 'pool-1',
        ]);
        ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Équipe A',
            'source' => 'desimone',
            'external_id' => 'team-1',
        ]);
        $ranking = [[
            'id_engagement' => ['id' => 'team-1', 'nom' => 'Équipe A'],
            'position' => 1,
            'points' => 2,
            'match_joues' => 1,
            'gagnes' => 1,
            'perdus' => 0,
            'paniers_marques' => 59,
            'paniers_encaisses' => 15,
            'penalites_arbitrage' => 1,
            'penalites_entraineur' => 2,
            'penalites_diverses' => 3,
        ]];
        $service = app(ChampionshipService::class);
        $snapshot = $service->refreshStanding($championship, $ranking);
        $entry = $snapshot->entries()->with('championshipTeam')->firstOrFail();

        $this->assertSame('Équipe A', $entry->championshipTeam->name);
        $this->assertDatabaseHas('championship_standing_entries', [
            'standing_snapshot_id' => $snapshot->id,
            'rank' => 1,
            'played' => 1,
            'won' => 1,
            'lost' => 0,
            'points_for' => 59,
            'points_against' => 15,
            'points' => 2,
            'penalty_points' => 6,
        ]);

        $ranking[0]['points'] = 3;
        $updatedSnapshot = $service->refreshStanding($championship, $ranking);
        $this->assertSame($snapshot->id, $updatedSnapshot->id);
        $this->assertDatabaseCount('championship_standing_snapshots', 1);
        $this->assertDatabaseCount('championship_standing_entries', 1);
        $this->assertDatabaseHas('championship_standing_entries', [
            'standing_snapshot_id' => $snapshot->id,
            'points' => 3,
        ]);
    }

    public function test_it_refreshes_all_rounds_of_the_selected_desimone_pool(): void
    {
        $championship = Championship::create([
            'name' => 'Championnat Desimone',
            'season' => '2026-2027',
            'source' => 'desimone',
            'external_id' => '200000002898776',
            'pool_external_id' => '200000003063776',
        ]);

        $matches = [
            $this->desimoneMatch('match-1', '1', '26', '50', null),
            $this->desimoneMatch('match-2', '1', 'None', 'None',null),
            $this->desimoneMatch('match-3', '2', '60', '55',null),
        ];


        Http::fake([
            'ffbb-api.desimone.fr/api/v1/competition/200000002898776' => Http::response([
                'id' => '200000002898776',
                'phases' => [[
                    'poules' => [[
                        'id' => '200000003063776',
                        'rencontres' => $matches,
                    ]],
                ]],
            ]),
            'ffbb-api.desimone.fr/api/v1/poule/200000003063776/classement' => Http::response([
                'classement' => $this->desimoneRanking($matches),
            ]),
        ]);

        $importedGames = app(ChampionshipService::class)->refresh($championship);

        $this->assertSame(3, $importedGames);
        $this->assertDatabaseCount('championship_gamedays', 2);
        $this->assertDatabaseHas('championship_gamedays', [
            'championship_id' => $championship->id,
            'source' => 'desimone',
            'number' => 1,
            'external_id' => '200000003063776:1',
        ]);
        $this->assertDatabaseHas('championship_games', [
            'source' => 'desimone',
            'external_id' => 'match-1',
            'home_score' => 26,
            'away_score' => 50,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('championship_games', [
            'source' => 'desimone',
            'external_id' => 'match-2',
            'home_score' => null,
            'away_score' => null,
            'status' => 'scheduled',
        ]);
        $this->assertDatabaseHas('championship_games', [
            'source' => 'desimone',
            'external_id' => 'match-3',
        ]);
        $this->assertDatabaseHas('championship_standing_entries', [
            'rank' => 1,
            'played' => 3,
            'won' => 2,
            'lost' => 1,
            'points_for' => 68,
            'points_against' => 65,
            'points' => 5,
        ]);

        $this->assertSame(3, app(ChampionshipService::class)->refresh($championship));
        $this->assertDatabaseCount('championship_gamedays', 2);
        $this->assertDatabaseCount('championship_games', 3);
        $this->assertDatabaseCount('championship_teams', 6);
        $this->assertDatabaseCount('championship_standing_snapshots', 1);
        $this->assertDatabaseCount('championship_standing_entries', 6);
        Http::assertSentCount(4);
    }

    public function test_it_imports_matches_and_adds_missing_championship_teams(): void
    {
        $championship = Championship::create([
            'name' => 'Championnat Desimone',
            'season' => '2026-2027',
            'source' => 'desimone',
            'external_id' => '200000002898776',
            'pool_external_id' => '200000003063776',
        ]);
        $match = $this->desimoneMatch('match-1', '1', '59', '15',null);

        Http::fake([
            'ffbb-api.desimone.fr/api/v1/competition/200000002898776' => Http::response([
                'id' => '200000002898776',
                'phases' => [[
                    'poules' => [[
                        'id' => '200000003063776',
                        'rencontres' => [$match],
                    ]],
                ]],
            ]),
            'ffbb-api.desimone.fr/api/v1/poule/200000003063776/classement' => Http::response([
                'classement' => [],
            ]),
        ]);

        $service = app(ChampionshipService::class);
        $this->assertSame(1, $service->refresh($championship));
        $this->assertDatabaseCount('championship_teams', 2);
        $this->assertDatabaseHas('championship_gamedays', [
            'championship_id' => $championship->id,
            'number' => 1,
            'source' => 'desimone',
        ]);
        $this->assertDatabaseHas('championship_games', [
            'source' => 'desimone',
            'external_id' => 'match-1',
            'home_score' => 59,
            'away_score' => 15,
            'status' => 'completed',
        ]);

        $this->assertSame(1, $service->refresh($championship));
        $this->assertDatabaseCount('championship_teams', 2);
        $this->assertDatabaseCount('championship_gamedays', 1);
        $this->assertDatabaseCount('championship_games', 1);
        Http::assertSentCount(4);
    }

    public function test_it_does_not_create_standing_entries_when_desimone_has_no_ranking(): void
    {
        $championship = Championship::create([
            'name' => 'Championnat Desimone',
            'season' => '2026-2027',
            'source' => 'desimone',
            'external_id' => '200000002898776',
            'pool_external_id' => '200000003063776',
        ]);
        $match = $this->desimoneMatch('match-new', '1', 'None', 'None',null);

        Http::fake([
            'ffbb-api.desimone.fr/api/v1/competition/200000002898776' => Http::response([
                'id' => '200000002898776',
                'phases' => [[
                    'poules' => [[
                        'id' => '200000003063776',
                        'rencontres' => [$match],
                    ]],
                ]],
            ]),
            'ffbb-api.desimone.fr/api/v1/poule/200000003063776/classement' => Http::response([
                'classement' => [],
            ]),
        ]);

        $this->assertSame(1, app(ChampionshipService::class)->refresh($championship));

        $this->assertDatabaseCount('championship_teams', 2);
        $this->assertDatabaseCount('championship_gamedays', 1);
        $this->assertDatabaseCount('championship_games', 1);
        $this->assertDatabaseCount('championship_standing_snapshots', 1);
        $this->assertDatabaseCount('championship_standing_entries', 0);

        $this->assertSame(1, app(ChampionshipService::class)->refresh($championship));
        $this->assertDatabaseCount('championship_teams', 2);
        $this->assertDatabaseCount('championship_games', 1);
        $this->assertDatabaseCount('championship_standing_entries', 0);
    }

    public function test_it_refreshes_a_desimone_championship_when_a_past_game_has_no_score(): void
    {
        Carbon::setTestNow('2026-11-10 12:00:00');

        try {
            $championship = Championship::create([
                'name' => 'Championnat Desimone',
                'season' => '2026-2027',
                'source' => 'desimone',
                'external_id' => '200000002898776',
                'pool_external_id' => '200000003063776',
            ]);
            $matchday = ChampionshipGamedays::create([
                'championship_id' => $championship->id,
                'number' => 1,
                'date' => '2026-11-07',
                'source' => 'desimone',
                'external_id' => '200000003063776:1',
            ]);
            $matches = [
                $this->desimoneMatch('match-1', '1', '26', '50',null),
                $this->desimoneMatch('match-2', '1', '30', '40',null),
                $this->desimoneMatch('match-3', '2', '60', '55',null),
            ];
            $homeTeam = ChampionshipTeam::create([
                'championship_id' => $championship->id,
                'name' => $matches[0]['nomEquipe1'],
                'source' => 'desimone',
                'external_id' => $matches[0]['idEngagementEquipe1']['id'],
            ]);
            $awayTeam = ChampionshipTeam::create([
                'championship_id' => $championship->id,
                'name' => $matches[0]['nomEquipe2'],
                'source' => 'desimone',
                'external_id' => $matches[0]['idEngagementEquipe2']['id'],
            ]);
            ChampionshipGames::create([
                'matchday_id' => $matchday->id,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'scheduled_at' => '2026-11-07 12:00:00',
                'source' => 'desimone',
                'external_id' => 'match-1',
            ]);

            Http::fake([
                'ffbb-api.desimone.fr/api/v1/competition/200000002898776' => Http::response([
                    'id' => '200000002898776',
                    'phases' => [[
                        'poules' => [[
                            'id' => '200000003063776',
                            'rencontres' => $matches,
                        ]],
                    ]],
                ]),
                'ffbb-api.desimone.fr/api/v1/poule/200000003063776/classement' => Http::response([
                    'classement' => $this->desimoneRanking($matches),
                ]),
            ]);

            $importedGames = app(ChampionshipService::class)->refresh($championship);

            $this->assertSame(3, $importedGames);
            $this->assertDatabaseHas('championship_games', [
                'source' => 'desimone',
                'external_id' => 'match-1',
                'home_score' => 26,
                'away_score' => 50,
                'status' => 'completed',
            ]);
            $this->assertDatabaseHas('championship_games', [
                'source' => 'desimone',
                'external_id' => 'match-3',
                'home_score' => 60,
                'away_score' => 55,
            ]);
            Http::assertSentCount(2);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function desimoneMatch(string $id, string $round, string $homeScore, string $awayScore,?array $salle): array
    {
        if (!$salle) {
            $salle = $this->desimoneSalle(
                fake()->randomNumber(5,false),
                fake()->word(),
                fake()->numerify('#####'),
                fake()->words(3,true),
                fake()->words(3,true),
                fake()->randomFloat(12,43,45),
                fake()->randomFloat(12,0,2),
            );
        }

        return [
            'id' => $id,
            'numeroJournee' => $round,
            'date_rencontre' => '2026-11-07T12:00:00',
            'resultatEquipe1' => $homeScore,
            'resultatEquipe2' => $awayScore,
            'joue' => $homeScore === 'None' ? 0 : 1,
            'nomEquipe1' => 'Équipe domicile '.$id,
            'nomEquipe2' => 'Équipe extérieure '.$id,
            'idEngagementEquipe1' => ['id' => 'home-'.$id],
            'idEngagementEquipe2' => ['id' => 'away-'.$id],
            'salle' => $salle,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches
     * @return array<int, array<string, mixed>>
     */
    private function desimoneRanking(array $matches): array
    {
        $ranking = [];
        $teamIds = [];

        foreach ($matches as $match) {
            foreach ([1, 2] as $teamNumber) {
                $name = $match['nomEquipe'.$teamNumber];
                $id = $match['idEngagementEquipe'.$teamNumber]['id'];
                $teamIds[$id] ??= $name;
            }
        }

        foreach ($teamIds as $id => $name) {
            $ranking[] = [
                'id_engagement' => ['id' => $id, 'nom' => $name],
                'position' => count($ranking) + 1,
                'points' => 5,
                'match_joues' => 3,
                'gagnes' => 2,
                'perdus' => 1,
                'paniers_marques' => 68,
                'paniers_encaisses' => 65,
            ];
        }

        return $ranking;
    }
    /**
     * @return array<string, mixed>
     */
    private function desimoneSalle(string $id, string $city, string $cp, string $label, string $address, float $lat, float $lng): array
    {
        return [
            "libelle" => $label,
            "libelle2"=>"",
            "adresse"=>$address,
            "adresseComplement"=>"",
            "commune" => [
                "codePostal" => $cp,
                "libelle" => $city
            ],
            "cartographie" => [
                "latitude" => $lat,
                "longitude" => $lng 
            ],
        ];
    }
}
