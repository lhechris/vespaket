<?php

namespace Tests\Feature;

use App\Livewire\Championship\Games\Create;
use App\Livewire\Championship\Games\Edit;
use App\Livewire\Championship\Games\Index;
use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\ChampionshipTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChampionshipGameManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_owner_can_view_games_but_cannot_see_edit_actions(): void
    {
        [$teamOwner, $team, $championship, $gameday, $homeTeam, $awayTeam] = $this->createChampionship();
        $game = $gameday->games()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($teamOwner)
            ->test(Index::class, [
                'team' => $team,
                'championship' => $championship,
                'gameday' => $gameday,
            ])
            ->assertSee($homeTeam->name)
            ->assertSee($awayTeam->name)
            ->assertDontSee(route('team.championship.gamedays.games.edit', [$team, $championship, $gameday, $game]));
    }

    public function test_an_admin_can_create_a_game_for_the_selected_gameday(): void
    {
        [, $team, $championship, $gameday, $homeTeam, $awayTeam] = $this->createChampionship();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(Create::class, [
                'team' => $team,
                'championship' => $championship,
                'gameday' => $gameday,
            ])
            ->set('homeTeamId', (string) $homeTeam->id)
            ->set('awayTeamId', (string) $awayTeam->id)
            ->set('scheduledAt', '2026-11-06T19:00')
            ->set('homeScore', '72')
            ->set('awayScore', '65')
            ->set('status', 'completed')
            ->set('location', 'Gymnase municipal')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('team.championship.gamedays.games.index', [$team, $championship, $gameday]));

        $this->assertDatabaseHas('championship_games', [
            'matchday_id' => $gameday->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_score' => 72,
            'away_score' => 65,
            'status' => 'completed',
            'location' => 'Gymnase municipal',
            'source' => 'manual',
        ]);
    }

    public function test_a_coach_cannot_create_a_game(): void
    {
        [$teamOwner, $team, $championship, $gameday] = $this->createChampionship();

        Livewire::actingAs($teamOwner)
            ->test(Create::class, [
                'team' => $team,
                'championship' => $championship,
                'gameday' => $gameday,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('championship_games', 0);
    }

    public function test_an_admin_can_edit_a_game_without_changing_its_import_metadata(): void
    {
        [, $team, $championship, $gameday, $homeTeam, $awayTeam] = $this->createChampionship();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $game = $gameday->games()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'scheduled_at' => '2026-11-06 19:00:00',
            'status' => 'scheduled',
            'source' => 'scorenco',
            'external_id' => 'scorenco-game-123',
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, [
                'team' => $team,
                'championship' => $championship,
                'gameday' => $gameday,
                'game' => $game,
            ])
            ->assertSet('homeTeamId', (string) $homeTeam->id)
            ->set('homeScore', '81')
            ->set('awayScore', '79')
            ->set('status', 'completed')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('team.championship.gamedays.games.index', [$team, $championship, $gameday]));

        $this->assertDatabaseHas('championship_games', [
            'id' => $game->id,
            'home_score' => 81,
            'away_score' => 79,
            'status' => 'completed',
            'source' => 'scorenco',
            'external_id' => 'scorenco-game-123',
        ]);
    }

    public function test_it_rejects_reusing_the_same_team_for_both_sides(): void
    {
        [, $team, $championship, $gameday, $homeTeam] = $this->createChampionship();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(Create::class, [
                'team' => $team,
                'championship' => $championship,
                'gameday' => $gameday,
            ])
            ->set('homeTeamId', (string) $homeTeam->id)
            ->set('awayTeamId', (string) $homeTeam->id)
            ->call('save')
            ->assertHasErrors(['homeTeamId' => 'different']);

        $this->assertDatabaseCount('championship_games', 0);
    }

    /**
     * @return array{0: User, 1: Team, 2: Championship, 3: ChampionshipGamedays, 4: ChampionshipTeam, 5: ChampionshipTeam}
     */
    private function createChampionship(): array
    {
        $teamOwner = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($teamOwner);
        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        $championship->teams()->create([
            'team_id' => $team->id,
            'name' => $team->name,
        ]);
        $gameday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
        ]);
        $homeTeam = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Équipe domicile',
        ]);
        $awayTeam = ChampionshipTeam::create([
            'championship_id' => $championship->id,
            'name' => 'Équipe extérieure',
        ]);

        return [$teamOwner, $team, $championship, $gameday, $homeTeam, $awayTeam];
    }
}
