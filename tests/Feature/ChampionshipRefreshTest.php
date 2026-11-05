<?php

namespace Tests\Feature;

use App\Livewire\Championship\Refresh;
use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ChampionshipRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_and_updates_matches_from_all_returned_competitions(): void
    {
        [$owner, $team] = $this->createOwnedTeam();
        $matches = $this->matchPayload();
        $championship = Championship::create([
            'name' => 'Ancien nom',
            'season' => '2025-2026',
            'source' => 'scorenco',
            'external_id' => '1241277',
        ]);
        $home = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Ancien nom équipe',
            'source' => 'scorenco',
            'external_id' => '1699927',
        ]);
        $away = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'AS LABARTHE SUR LEZE',
            'source' => 'scorenco',
            'external_id' => '1699922',
        ]);
        $matchday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
            'date' => '2026-11-01',
            'source' => 'scorenco',
            'external_id' => 'date:2026-11-01',
        ]);
        ChampionshipGames::create([
            'matchday_id' => $matchday->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_score' => 40,
            'away_score' => 35,
            'status' => 'scheduled',
            'source' => 'scorenco',
            'external_id' => '15038290',
        ]);

        Http::fake([
            'graphql.scorenco.com/*' => Http::response([
                'data' => ['competitions_event_detail_by_team_id' => $matches],
            ]),
        ]);

        Livewire::actingAs($owner)
            ->test(Refresh::class, ['team' => $team])
            ->set('scorencoTeamId', '138142')
            ->call('refresh')
            ->assertHasNoErrors()
            ->assertSee('3 match(s) Scorenco synchronisé(s).');

        $this->assertDatabaseCount('championships', 2);
        $this->assertDatabaseCount('championship_games', 3);
        $this->assertSame(2, ChampionshipTeam::query()->where('team_id', $team->id)->count());
        $this->assertDatabaseHas('championship_games', [
            'source' => 'scorenco',
            'external_id' => '15038290',
            'home_score' => 78,
            'away_score' => 52,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('championships', [
            'source' => 'scorenco',
            'external_id' => '1241277',
            'season' => '2026-2027',
        ]);

        Livewire::actingAs($owner)
            ->test(Refresh::class, ['team' => $team])
            ->set('scorencoTeamId', '138142')
            ->call('refresh')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('championships', 2);
        $this->assertDatabaseCount('championship_teams', 5);
        $this->assertDatabaseCount('championship_games', 3);
        $this->assertDatabaseHas('championship_games', [
            'source' => 'scorenco',
            'external_id' => '15038290',
            'home_score' => 78,
        ]);
    }

    public function test_it_requires_a_scorenco_team_id(): void
    {
        [$owner, $team] = $this->createOwnedTeam();

        Livewire::actingAs($owner)
            ->test(Refresh::class, ['team' => $team])
            ->call('refresh')
            ->assertHasErrors(['scorencoTeamId' => 'required']);

        $this->assertDatabaseCount('championships', 0);
    }

    public function test_a_user_who_does_not_own_the_team_cannot_refresh_its_championships(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($owner);
        $otherUser = User::factory()->create();

        Livewire::actingAs($otherUser)
            ->test(Refresh::class, ['team' => $team])
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function createOwnedTeam(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        return [$owner, $team];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function matchPayload(): array
    {
        return [
            [
                'id' => 15038290,
                'status' => 'finish',
                'date' => '2026-11-01',
                'time' => '2026-11-01T14:30:00+00:00',
                'level_name' => 'Pré Nationale Féminine',
                'competition_id' => 1241277,
                'teams' => [
                    [
                        'role' => 'home',
                        'score' => 78,
                        'team_id' => 138142,
                        'team_in_season_id' => 1699927,
                        'name_in_competition' => 'BASKET ELUSA CLUB',
                    ],
                    [
                        'role' => 'away',
                        'score' => 52,
                        'team_id' => 1381420,
                        'team_in_season_id' => 1699922,
                        'name_in_competition' => 'AS LABARTHE SUR LEZE',
                    ],
                ],
            ],
            [
                'id' => 15038239,
                'status' => 'scheduled',
                'date' => '2026-11-08',
                'time' => '2026-11-08T13:30:00+00:00',
                'level_name' => 'Pré Nationale Féminine',
                'competition_id' => 1241277,
                'teams' => [
                    [
                        'role' => 'home',
                        'score' => null,
                        'team_id' => 138142,
                        'team_in_season_id' => 1699927,
                        'name_in_competition' => 'BASKET ELUSA CLUB',
                    ],
                    [
                        'role' => 'away',
                        'score' => null,
                        'team_id' => 130464,
                        'team_in_season_id' => 1699904,
                        'name_in_competition' => 'VACQUIERS BOULOC BASKET',
                    ],
                ],
            ],
            [
                'id' => 15038999,
                'status' => 'finish',
                'date' => '2026-11-15',
                'time' => '2026-11-15T13:30:00+00:00',
                'level_name' => 'Coupe régionale',
                'competition_id' => 1241278,
                'teams' => [
                    [
                        'role' => 'home',
                        'score' => 61,
                        'team_id' => 138142,
                        'team_in_season_id' => 1699928,
                        'name_in_competition' => 'BASKET ELUSA CLUB',
                    ],
                    [
                        'role' => 'away',
                        'score' => 48,
                        'team_id' => 130465,
                        'team_in_season_id' => 1699905,
                        'name_in_competition' => 'UNION SPORTIVE',
                    ],
                ],
            ],
        ];
    }
}
