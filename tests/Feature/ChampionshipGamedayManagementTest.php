<?php

namespace Tests\Feature;

use App\Livewire\Championship\Gameday\Create;
use App\Livewire\Championship\Gameday\Edit;
use App\Livewire\Championship\Gameday\Index;
use App\Models\Championship;
use App\Models\ChampionshipGamedays;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChampionshipGamedayManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_owner_can_list_its_championship_gamedays(): void
    {
        [$owner, $team, $championship] = $this->createOwnedChampionship();
        $gameday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 2,
            'label' => 'J2',
            'date' => '2026-09-27',
        ]);

        Livewire::actingAs($owner)
            ->test(Index::class, ['team' => $team, 'championship' => $championship])
            ->assertSee('J2')
            ->assertSee('27 septembre 2026')
            ->assertDontSee(route('team.championship.gamedays.edit', [$team, $championship, $gameday]));
    }

    public function test_an_admin_can_create_a_gameday(): void
    {
        [, $team, $championship] = $this->createOwnedChampionship();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(Create::class, ['team' => $team, 'championship' => $championship])
            ->set('number', '1')
            ->set('label', 'J1')
            ->set('date', '2026-09-20')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('team.championship.edit', [$team, $championship]));

        $this->assertDatabaseHas('championship_gamedays', [
            'championship_id' => $championship->id,
            'number' => 1,
            'label' => 'J1',
            'date' => '2026-09-20 00:00:00',
            'source' => 'manual',
        ]);
    }

    public function test_a_gameday_number_must_be_unique_within_its_championship(): void
    {
        [, $team, $championship] = $this->createOwnedChampionship();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(Create::class, ['team' => $team, 'championship' => $championship])
            ->set('number', '1')
            ->call('save')
            ->assertHasErrors(['number' => 'unique']);

        $this->assertDatabaseCount('championship_gamedays', 1);
    }

    public function test_an_admin_can_edit_a_gameday(): void
    {
        [, $team, $championship] = $this->createOwnedChampionship();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $gameday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
            'label' => 'J1',
            'date' => '2026-09-20',
            'source' => 'scorenco',
            'external_id' => 'round-1',
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['team' => $team, 'championship' => $championship, 'gameday' => $gameday])
            ->assertSet('number', '1')
            ->set('number', '2')
            ->set('label', 'J2')
            ->set('date', '2026-09-27')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('team.championship.edit', [$team, $championship]));

        $this->assertDatabaseHas('championship_gamedays', [
            'id' => $gameday->id,
            'number' => 2,
            'label' => 'J2',
            'date' => '2026-09-27 00:00:00',
            'source' => 'scorenco',
            'external_id' => 'round-1',
        ]);
    }

    public function test_a_coach_cannot_create_a_gameday(): void
    {
        [$coach, $team, $championship] = $this->createOwnedChampionship();

        Livewire::actingAs($coach)
            ->test(Create::class, ['team' => $team, 'championship' => $championship])
            ->assertForbidden();

        $this->assertDatabaseCount('championship_gamedays', 0);
    }

    public function test_a_coach_cannot_edit_a_gameday(): void
    {
        [$coach, $team, $championship] = $this->createOwnedChampionship();
        $gameday = ChampionshipGamedays::create([
            'championship_id' => $championship->id,
            'number' => 1,
            'label' => 'J1',
        ]);

        Livewire::actingAs($coach)
            ->test(Edit::class, ['team' => $team, 'championship' => $championship, 'gameday' => $gameday])
            ->assertForbidden();

        $this->assertDatabaseHas('championship_gamedays', [
            'id' => $gameday->id,
            'number' => 1,
            'label' => 'J1',
        ]);
    }

    public function test_a_user_cannot_manage_gamedays_for_a_championship_not_linked_to_their_team(): void
    {
        [$owner, $team] = $this->createOwnedTeam();
        $championship = Championship::create([
            'name' => 'Autre championnat',
            'season' => '2026-2027',
        ]);

        Livewire::actingAs($owner)
            ->test(Index::class, ['team' => $team, 'championship' => $championship])
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Team, 2: Championship}
     */
    private function createOwnedChampionship(): array
    {
        [$owner, $team] = $this->createOwnedTeam();
        $championship = Championship::create([
            'name' => 'Championnat test',
            'season' => '2026-2027',
        ]);
        $championship->teams()->create([
            'team_id' => $team->id,
            'name' => $team->name,
        ]);

        return [$owner, $team, $championship];
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
}
