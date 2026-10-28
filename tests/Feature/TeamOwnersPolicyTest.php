<?php

namespace Tests\Feature;

use App\Livewire\Team\Owners;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamOwnersPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_team_owners(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $team = Team::factory()->create();
        $newOwner = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(Owners::class, ['team' => $team])
            ->call('addOwner', $newOwner->id)
            ->assertHasNoErrors();

        $this->assertTrue($team->owners()->whereKey($newOwner->id)->exists());
    }

    public function test_a_team_owner_can_manage_owners_but_another_coach_cannot(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_COACH]);
        $otherCoach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        Livewire::actingAs($owner)
            ->test(Owners::class, ['team' => $team])
            ->assertSuccessful();

        Livewire::actingAs($otherCoach)
            ->test(Owners::class, ['team' => $team])
            ->assertForbidden();
    }
}
