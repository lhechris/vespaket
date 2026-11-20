<?php

namespace Tests\Feature;

use App\Livewire\Team\Show;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_show_defaults_to_the_games_tab(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Équipe Test']);
        $team->owners()->attach($owner);

        Livewire::actingAs($owner)
            ->test(Show::class, ['team' => $team])
            ->assertSet('activeTab', 'games')
            ->assertSee('Équipe Test');
    }

    public function test_team_show_can_switch_tabs(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        Livewire::actingAs($owner)
            ->test(Show::class, ['team' => $team])
            ->call('setTab', 'members')
            ->assertSet('activeTab', 'members')
            ->call('setTab', 'trainings')
            ->assertSet('activeTab', 'trainings')
            ->call('setTab', 'parameters')
            ->assertSet('activeTab', 'parameters');
    }
}
