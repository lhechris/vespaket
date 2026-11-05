<?php

namespace Tests\Feature;

use App\Livewire\Championship\Index;
use App\Models\Championship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChampionshipIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_owner_can_list_but_not_edit_championships_for_their_team(): void
    {
        [$owner, $team] = $this->createOwnedTeam();
        $championship = Championship::create([
            'name' => 'Championnat départemental',
            'season' => '2026-2027',
        ]);
        $championship->teams()->create([
            'team_id' => $team->id,
            'name' => $team->name,
        ]);
        $otherChampionship = Championship::create([
            'name' => 'Autre championnat',
            'season' => '2026-2027',
        ]);

        Livewire::actingAs($owner)
            ->test(Index::class, ['team' => $team])
            ->assertSee('Championnat départemental')
            ->assertDontSee('Modifier')
            ->assertDontSee(route('team.championship.edit', [$team, $championship]))
            ->assertDontSee('Autre championnat');
    }

    public function test_it_displays_an_empty_state_when_the_team_has_no_championships(): void
    {
        [$owner, $team] = $this->createOwnedTeam();

        Livewire::actingAs($owner)
            ->test(Index::class, ['team' => $team])
            ->assertSee('Aucun championnat n’est encore associé à cette équipe.')
            ->assertDontSee('Créer le premier championnat')
            ->assertDontSee(route('team.championship.manage', $team));
    }

    public function test_an_admin_can_see_championship_management_links(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $team = Team::factory()->create();
        $championship = Championship::create([
            'name' => 'Championnat départemental',
            'season' => '2026-2027',
        ]);
        $championship->teams()->create([
            'team_id' => $team->id,
            'name' => $team->name,
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class, ['team' => $team])
            ->assertSee('Modifier')
            ->assertSee(route('team.championship.edit', [$team, $championship]));
    }

    public function test_a_user_who_does_not_own_the_team_cannot_list_its_championships(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($owner);
        $otherCoach = User::factory()->create(['role' => User::ROLE_COACH]);

        Livewire::actingAs($otherCoach)
            ->test(Index::class, ['team' => $team])
            ->assertForbidden();
    }

    private function createOwnedTeam(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        return [$owner, $team];
    }
}
