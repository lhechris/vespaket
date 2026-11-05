<?php

namespace Tests\Feature;

use App\Livewire\Championship\Manage;
use App\Models\Championship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChampionshipCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_a_manual_championship_for_a_team(): void
    {
        [, $team] = $this->createOwnedTeam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(Manage::class, ['team' => $team])
            ->set('name', 'Championnat départemental')
            ->set('season', '2026-2027')
            ->set('category', 'U13')
            ->set('division', 'Division 2')
            ->set('pool', 'Poule A')
            ->set('scorencoteamid', '98765')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('championship.show', ['team' => $team]));

        $championship = Championship::query()->sole();

        $this->assertDatabaseHas('championships', [
            'id' => $championship->id,
            'name' => 'Championnat départemental',
            'season' => '2026-2027',
            'category' => 'U13',
            'division' => 'Division 2',
            'pool' => 'Poule A',
            'source' => 'manual',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('championship_teams', [
            'championship_id' => $championship->id,
            'team_id' => $team->id,
            'name' => $team->name,
            'source' => 'scorenco',
            'external_id' => '98765',
        ]);
    }

    public function test_championship_name_and_season_are_required(): void
    {
        [, $team] = $this->createOwnedTeam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Livewire::actingAs($admin)
            ->test(Manage::class, ['team' => $team])
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'season' => 'required']);

        $this->assertDatabaseCount('championships', 0);
    }

    public function test_a_user_who_does_not_own_the_team_cannot_create_its_championship(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($owner);
        $otherCoach = User::factory()->create(['role' => User::ROLE_COACH]);

        Livewire::actingAs($otherCoach)
            ->test(Manage::class, ['team' => $team])
            ->assertForbidden();

        $this->assertDatabaseCount('championships', 0);
    }

    public function test_a_coach_cannot_create_a_championship(): void
    {
        [, $team] = $this->createOwnedTeam();
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team->owners()->attach($coach);

        Livewire::actingAs($coach)
            ->test(Manage::class, ['team' => $team])
            ->assertForbidden();

        $this->assertDatabaseCount('championships', 0);
    }

    public function test_an_admin_can_update_a_championship(): void
    {
        [, $team] = $this->createOwnedTeam();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $championship = Championship::create([
            'name' => 'Ancien nom',
            'season' => '2025-2026',
            'category' => 'U13',
        ]);
        $championshipTeam = $championship->teams()->create([
            'team_id' => $team->id,
            'name' => $team->name,
            'source' => 'scorenco',
            'external_id' => '12345',
        ]);

        Livewire::actingAs($admin)
            ->test(Manage::class, ['team' => $team, 'championship' => $championship])
            ->assertSet('name', 'Ancien nom')
            ->assertSet('scorencoteamid', '12345')
            ->set('name', 'Nouveau nom')
            ->set('season', '2026-2027')
            ->set('scorencoteamid', '67890')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('championship.show', ['team' => $team]));

        $this->assertDatabaseHas('championships', [
            'id' => $championship->id,
            'name' => 'Nouveau nom',
            'season' => '2026-2027',
            'category' => 'U13',
        ]);
        $this->assertDatabaseHas('championship_teams', [
            'id' => $championshipTeam->id,
            'external_id' => '67890',
        ]);
        $this->assertDatabaseCount('championships', 1);
    }

    public function test_a_coach_cannot_update_a_championship(): void
    {
        [$coach, $team] = $this->createOwnedTeam();
        $championship = Championship::create([
            'name' => 'Ancien nom',
            'season' => '2025-2026',
        ]);
        $championship->teams()->create([
            'team_id' => $team->id,
            'name' => $team->name,
        ]);

        Livewire::actingAs($coach)
            ->test(Manage::class, ['team' => $team, 'championship' => $championship])
            ->assertForbidden();

        $this->assertDatabaseHas('championships', [
            'id' => $championship->id,
            'name' => 'Ancien nom',
        ]);
    }

    public function test_a_team_owner_cannot_edit_a_championship_linked_to_another_team(): void
    {
        [$owner, $team] = $this->createOwnedTeam();
        $otherTeam = Team::factory()->create();
        $championship = Championship::create([
            'name' => 'Championnat',
            'season' => '2026-2027',
        ]);
        $championship->teams()->create([
            'team_id' => $otherTeam->id,
            'name' => $otherTeam->name,
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class, ['team' => $team, 'championship' => $championship])
            ->assertNotFound();
    }

    private function createOwnedTeam(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        return [$owner, $team];
    }
}
