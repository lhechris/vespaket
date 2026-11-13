<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Game;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPendingGamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_dispatches_a_pending_game_notification_once_per_session(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PLAYER]);
        $member = Member::factory()->create();
        $user->members()->attach($member->id, ['relation' => 'self']);

        $team = Team::factory()->create();
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'date' => now()->addDay(),
        ]);
        $game->members()->attach($member->id, ['availability' => null]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertDispatched('notify')
            ->call('checkPendingGames')
            ->assertNotDispatched('notify');
    }
}
