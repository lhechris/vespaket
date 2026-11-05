<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Services\ScorencoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScorencoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refreshes_a_round_with_teams_games_scores_and_location(): void
    {
        $championship = Championship::create([
            'name' => 'Pré Nationale Féminine',
            'season' => '2026-2027',
        ]);
        $response = ['data' => [
            'competitions_event_detail_by_round_day_id' => $this->roundEvents(),
        ]];
        Http::fake(['graphql.scorenco.com/*' => Http::response($response)]);

        $updated = app(ScorencoService::class)->refreshRoundEvents(14284752, $championship->id);

        $this->assertSame(2, $updated);
        $this->assertDatabaseHas('championship_gamedays', [
            'championship_id' => $championship->id,
            'number' => 1,
            'source' => 'scorenco',
            'external_id' => '14284752',
        ]);
        $this->assertSame(
            '2026-10-31',
            $championship->gamedays()->firstOrFail()->date->toDateString()
        );
        $this->assertDatabaseHas('championship_teams', [
            'championship_id' => $championship->id,
            'name' => 'BASKET CLUB LAVAUR',
            'source' => 'scorenco',
            'external_id' => '1699919',
        ]);
        $this->assertDatabaseHas('championship_games', [
            'source' => 'scorenco',
            'external_id' => '15038281',
            'home_score' => 68,
            'away_score' => 72,
            'status' => 'completed',
            'location' => 'SALLE DES CLAUZADES N 2 Route de Caraman 81500 Lavaur',
        ]);

        Http::fake(['graphql.scorenco.com/*' => Http::response($response)]);
        $updated = app(ScorencoService::class)->refreshRoundEvents(14284752, $championship->id);

        $this->assertSame(2, $updated);
        $this->assertDatabaseCount('championship_gamedays', 1);
        $this->assertDatabaseCount('championship_teams', 4);
        $this->assertDatabaseCount('championship_games', 2);
    }

    public function test_it_reuses_a_scorenco_matchday_grouped_by_date(): void
    {
        $championship = Championship::create([
            'name' => 'Pré Nationale Féminine',
            'season' => '2026-2027',
        ]);
        ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 3,
            'date' => '2026-10-31',
            'source' => 'scorenco',
            'external_id' => 'date:2026-10-31',
        ]);
        Http::fake(['graphql.scorenco.com/*' => Http::response([
            'data' => [
                'competitions_event_detail_by_round_day_id' => [$this->roundEvents()[0]],
            ],
        ])]);

        app(ScorencoService::class)->refreshRoundEvents(14284752, $championship->id);

        $this->assertDatabaseCount('championship_gamedays', 1);
        $this->assertDatabaseHas('championship_gamedays', [
            'championship_id' => $championship->id,
            'number' => 3,
            'external_id' => '14284752',
        ]);
    }

    public function test_it_throws_when_scorenco_request_fails(): void
    {
        $championship = Championship::create([
            'name' => 'Pré Nationale Féminine',
            'season' => '2026-2027',
        ]);
        Http::fake(['graphql.scorenco.com/*' => Http::response('Service unavailable', 503)]);

        $this->expectException(\RuntimeException::class);

        app(ScorencoService::class)->refreshRoundEvents(14284752, $championship->id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function roundEvents(): array
    {
        return [
            [
                'id' => 15038281,
                'status' => 'finish',
                'time' => '2026-10-31T17:30:00+00:00',
                'date' => '2026-10-31',
                'level_name' => 'Pré Nationale Féminine',
                'place' => [
                    'id' => 1102851,
                    'name' => 'SALLE DES CLAUZADES N 2 Route de Caraman 81500 Lavaur',
                ],
                'teams' => [
                    [
                        'role' => 'home',
                        'score' => 68,
                        'team_id' => 111410,
                        'team_in_season_id' => 1699919,
                        'name_in_competition' => 'BASKET CLUB LAVAUR',
                    ],
                    [
                        'role' => 'away',
                        'score' => 72,
                        'team_id' => 123530,
                        'team_in_season_id' => 1699913,
                        'name_in_competition' => 'TLSE CHEMINOTS MARENGO SP',
                    ],
                ],
                'url' => '/basket/match-basket-club-lavaur-f1-tlse-cheminots-marengo-sp-f1-1qyll?e=15038281',
            ],
            [
                'id' => 15038269,
                'status' => 'finish',
                'time' => '2026-11-01T14:30:00+00:00',
                'date' => '2026-11-01',
                'level_name' => 'Pré Nationale Féminine',
                'place' => [
                    'id' => 1104612,
                    'name' => 'SALLE OMNISPORT DE BOULOC Route de Villaudric 31620 Bouloc',
                ],
                'teams' => [
                    [
                        'role' => 'home',
                        'score' => 61,
                        'team_id' => 122631,
                        'team_in_season_id' => 1699904,
                        'name_in_competition' => 'VACQUIERS BOULOC BASKET',
                    ],
                    [
                        'role' => 'away',
                        'score' => 69,
                        'team_id' => 123531,
                        'team_in_season_id' => 1699926,
                        'name_in_competition' => 'BASKET CLUB OSSUNOIS',
                    ],
                ],
                'url' => '/basket/match-basket-club-ossunois-f1-vacquiers-bouloc-basket-f1-xqha?e=15038269',
            ],
        ];
    }
}
