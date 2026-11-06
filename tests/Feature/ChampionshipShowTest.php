<?php

namespace Tests\Feature;

use App\Livewire\Championship\Show;
use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipGames;
use App\Models\ChampionshipStandingEntries;
use App\Models\ChampionshipStandingSnapshots;
use App\Models\ChampionshipTeam;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChampionshipShowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_displays_team_championship_fixtures_and_latest_standings(): void
    {
        Carbon::setTestNow('2026-11-02 12:00:00');

        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Vespaket U13']);
        $championship = Championship::create([
            'name' => 'Championnat départemental',
            'season' => '2026-2027',
            'category' => 'U13',
            'division' => 'Division 2',
            'pool' => 'Poule A',
        ]);
        $localTeam = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'team_id' => $team->id,
            'name' => $team->name,
        ]);
        $opponent = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Basket du Val',
        ]);
        $matchday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 3,
            'date' => '2026-11-07',
        ]);
        ChampionshipGames::create([
            'matchday_id' => $matchday->id,
            'home_team_id' => $localTeam->id,
            'away_team_id' => $opponent->id,
            'scheduled_at' => '2026-11-07 14:00:00',
            'status' => 'scheduled',
        ]);
        $snapshot = ChampionshipStandingSnapshots::create([
            'championship_id' => $championship->id,
            'matchday_id' => $matchday->id,
            'captured_at' => '2026-11-01 10:00:00',
        ]);
        ChampionshipStandingEntries::create([
            'standing_snapshot_id' => $snapshot->id,
            'championship_team_id' => $localTeam->id,
            'rank' => 1,
            'played' => 2,
            'won' => 2,
            'points_for' => 120,
            'points_against' => 90,
            'points' => 4,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team])
            ->assertSee('Championnat départemental')
            ->assertSee('Vespaket U13')
            ->assertSee('Basket du Val')
            ->assertSee('Journée 3')
            ->assertSee('Classement')
            ->assertSee('120')
            ->assertSee('Mis à jour le');

        Carbon::setTestNow();
    }

    public function test_it_displays_an_empty_state_when_the_team_has_no_championship(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Sans championnat']);

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team])
            ->assertSee('Aucun championnat n’est associé à cette équipe.');
    }

    public function test_it_can_switch_matchdays(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'team_id' => $team->id,
            'name' => $team->name,
        ]);
        $firstMatchday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
        ]);
        $secondMatchday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 2,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team])
            ->assertSet('selectedMatchdayId', $secondMatchday->id)
            ->assertSee('Journée précédente')
            ->assertSee('Journée suivante')
            ->assertSee('Journée 1')
            ->call('previousMatchday')
            ->assertSet('selectedMatchdayId', $firstMatchday->id)
            ->call('previousMatchday')
            ->assertSet('selectedMatchdayId', $firstMatchday->id)
            ->call('nextMatchday')
            ->assertSet('selectedMatchdayId', $secondMatchday->id)
            ->call('nextMatchday')
            ->assertSet('selectedMatchdayId', $secondMatchday->id)
            ->call('selectMatchday', $firstMatchday->id)
            ->assertSet('selectedMatchdayId', $firstMatchday->id)
            ->assertSee('Journée 1')
            ->assertSee('Aucune rencontre enregistrée pour cette journée.');
    }
}
