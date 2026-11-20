<?php

namespace Tests\Feature;

use App\Services\ScorencoService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScorencoServiceTest extends TestCase
{
    public function test_it_gets_matches_for_a_team_with_the_requested_filters_and_pagination(): void
    {
        $matches = [[
            'id' => 15038281,
            'status' => 'finish',
            'teams' => ['home' => ['score' => 68], 'away' => ['score' => 72]],
        ]];
        Http::fake([
            'graphql.scorenco.com/v1/graphql' => Http::response([
                'data' => [
                    'competitions_event_detail_by_team_id' => $matches,
                ],
            ]),
        ]);

        $result = app(ScorencoService::class)->getMatchesByTeamId(
            teamId: 12345,
            dateFilter: 2,
            limit: 5,
            offset: 10,
        );

        $this->assertSame($matches, $result);
        Http::assertSent(fn (Request $request): bool =>
            $request->url() === 'https://graphql.scorenco.com/v1/graphql'
            && $request->method() === 'POST'
            && $request['operationName'] === 'GetMatchs'
            && $request['variables'] === [
                'teamId' => 12345,
                'dateFilter' => 2,
                'offset' => 10,
                'limit' => 5,
            ]
            && str_contains($request['query'], 'competitions_event_detail_by_team_id')
        );
    }

    public function test_it_returns_an_empty_list_when_the_team_matches_field_is_missing(): void
    {
        Http::fake([
            'graphql.scorenco.com/v1/graphql' => Http::response(['data' => []]),
        ]);

        $this->assertSame([], app(ScorencoService::class)->getMatchesByTeamId(12345));
    }

    public function test_it_gets_events_for_a_round(): void
    {
        $events = [[
            'id' => 15038281,
            'date' => '2026-10-31',
            'place' => ['id' => 1102851, 'name' => 'Gymnase'],
            'teams' => [
                ['role' => 'home', 'score' => 68],
                ['role' => 'away', 'score' => 72],
            ],
        ]];
        Http::fake([
            'graphql.scorenco.com/v1/graphql' => Http::response([
                'data' => [
                    'competitions_event_detail_by_round_day_id' => $events,
                ],
            ]),
        ]);

        $result = app(ScorencoService::class)->getRoundEvents(14284752);

        $this->assertSame($events, $result);
        Http::assertSent(fn (Request $request): bool =>
            $request->url() === 'https://graphql.scorenco.com/v1/graphql'
            && $request->method() === 'POST'
            && $request['operationName'] === 'EventsByRoundId'
            && $request['variables'] === ['roundId' => 14284752]
            && str_contains($request['query'], 'competitions_event_detail_by_round_day_id')
        );
    }

    public function test_it_throws_when_the_round_events_field_is_missing(): void
    {
        Http::fake([
            'graphql.scorenco.com/v1/graphql' => Http::response(['data' => []]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La réponse Scorenco ne contient pas les matchs de la journée.');

        app(ScorencoService::class)->getRoundEvents(14284752);
    }

    public function test_it_throws_when_a_graphql_request_fails(): void
    {
        Http::fake([
            'graphql.scorenco.com/v1/graphql' => Http::response('Service unavailable', 503),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Erreur Scorenco : Service unavailable');

        app(ScorencoService::class)->getMatchesByTeamId(12345);
    }

    public function test_it_finds_the_configured_club_by_slug(): void
    {
        Http::fake([
            'search.scorenco.com/search' => Http::response([
                'hits' => [
                    ['id' => 'club-1', 'name' => 'Autre club', 'slug' => 'autre-club'],
                    ['id' => 'club-2', 'name' => 'ASLB Labarthe-sur-Lèze', 'slug' => 'aslb-labarthe-sur-leze'],
                ],
            ]),
        ]);

        $this->assertSame('club-2', app(ScorencoService::class)->getClubId('Labarthe'));
        Http::assertSent(fn (Request $request): bool =>
            $request->url() === 'https://search.scorenco.com/search'
            && $request->method() === 'POST'
            && $request->data() === [
                'sport_slug' => 'basket',
                'term' => 'Labarthe',
                'type' => 'club',
            ]
        );
    }

    public function test_it_throws_when_the_club_search_response_has_no_hits(): void
    {
        Http::fake([
            'search.scorenco.com/search' => Http::response(['results' => []]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("La réponse Scorenco ne contient pas l'attendu");

        app(ScorencoService::class)->getClubId('Labarthe');
    }
}
