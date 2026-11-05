<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipStandingEntries;
use App\Models\Team;
use Carbon\Carbon;
use Database\Seeders\ChampionshipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_seeds_a_demo_championship_with_teams_games_and_standings(): void
    {
        Carbon::setTestNow('2026-11-02 12:00:00');

        (new ChampionshipSeeder)->run();

        $championship = Championship::query()->sole();

        $this->assertSame('Championnat départemental', $championship->name);
        $this->assertSame(8, $championship->teams()->count());
        $this->assertSame(7, $championship->gamedays()->count());
        $this->assertSame(28, ChampionshipGames::query()->count());
        $this->assertSame(16, ChampionshipGames::query()->where('status', 'completed')->count());
        $this->assertSame(12, ChampionshipGames::query()->where('status', 'scheduled')->count());
        $this->assertSame(8, ChampionshipStandingEntries::query()->count());
        $this->assertNotNull($championship->teams()->whereNotNull('team_id')->first());
        $this->assertSame(
            'Basket Club du Val',
            $championship->teams()->where('external_id', 'demo-team-2')->value('name')
        );

        $playedGames = ChampionshipGames::query()->where('status', 'completed')->get();
        $this->assertSame(4, $playedGames->groupBy('matchday_id')->count());
        $this->assertTrue($playedGames->every(
            fn (ChampionshipGames $game): bool => $game->home_score !== null && $game->away_score !== null
        ));
    }

    public function test_it_can_be_run_more_than_once_without_creating_duplicates(): void
    {
        (new ChampionshipSeeder)->run();
        (new ChampionshipSeeder)->run();

        $this->assertDatabaseCount('championships', 1);
        $this->assertDatabaseCount('teams', 1);
        $this->assertDatabaseCount('championship_teams', 8);
        $this->assertDatabaseCount('championship_gamedays', 7);
        $this->assertDatabaseCount('championship_games', 28);
        $this->assertDatabaseCount('championship_standing_snapshots', 1);
        $this->assertDatabaseCount('championship_standing_entries', 8);
        $this->assertSame(1, Team::query()->where('name', 'Vespaket U13 (démo)')->count());
    }
}
