<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Event;
use App\Models\Game;
use App\Models\Licensee;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPendingGamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_combines_games_and_events_from_all_members_of_a_licensee(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PARENT]);
        $licensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
        ]);
        $user->licensees()->attach($licensee);

        $team = Team::factory()->create();
        $player = $licensee->members()->create([
            'type' => Member::TYPE_PLAYER,
            'team_id' => $team->id,
        ]);
        $substitute = $licensee->members()->create([
            'type' => Member::TYPE_SUB,
            'team_id' => $team->id,
        ]);

        $game = Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 1',
            'team_away' => 'Equipe 2',
            'date' => now()->addDay(),
        ]);
        $game->members()->attach($player->id, ['availability' => 'yes']);

        $substituteGame = Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 3',
            'team_away' => 'Equipe 4',
            'date' => now()->addDays(2),
        ]);
        $substituteGame->members()->attach($substitute->id, ['availability' => 'yes']);

        $event = Event::create([
            'team_id' => $team->id,
            'titre' => 'Événement du remplaçant',
            'date' => now()->addDays(3),
            'location' => 'Gymnase',
        ]);
        $event->members()->attach($substitute->id);

        $response = Livewire::actingAs($user)->test(Dashboard::class);

        $response
            ->assertSeeInOrder(['Equipe 1', 'Equipe 3', 'Événement du remplaçant']);

        $this->assertCount(3, $response->get('licensees')->first()->combined);
        $this->assertSame(1, substr_count($response->html(), 'max-h-[500px]'));
    }

    public function test_dashboard_only_loads_members_with_a_called_game(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PARENT]);
        $licensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
        ]);
        $user->licensees()->attach($licensee);
        $otherLicensee = Licensee::create([
            'firstname' => 'Bob',
            'name' => 'Martin',
        ]);
        $user->licensees()->attach($otherLicensee);

        $team = Team::factory()->create();
        $calledMember = $licensee->members()->create([
            'type' => Member::TYPE_PLAYER,
            'team_id' => $team->id,
        ]);
        $notCalledMember = $licensee->members()->create([
            'type' => Member::TYPE_SUB,
            'team_id' => $team->id,
        ]);

        $calledGame = Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 1 convoqué',
            'team_away' => 'Equipe 2',
            'date' => now()->addDay(),
        ]);
        $calledGame->members()->attach($calledMember->id, [
            'availability' => 'yes',
            'called' => true,
        ]);

        $unrelatedGame = Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 3 non convoqué du joueur',
            'team_away' => 'Equipe 4',
            'date' => now()->addDays(2),
        ]);
        $unrelatedGame->members()->attach($calledMember->id, [
            'availability' => 'yes',
            'called' => false,
        ]);

        $notCalledGame = Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 5 non convoqué',

            'team_away' => 'Equipe 6',
            'date' => now()->addDays(2),
        ]);
        $notCalledGame->members()->attach($notCalledMember->id, [
            'availability' => 'yes',
            'called' => false,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Equipe 1 convoqué')
            ->assertDontSee('Equipe 5 non convoqué')
            ->assertDontSee('Equipe 3 non convoqué du joueur')
            ->call('setTab', 'Bob')
            ->call('setTab', 'Alice')
            ->assertSee('Equipe 1 convoqué')
            ->assertDontSee('Equipe 5 non convoqué')
            ->assertDontSee('Equipe 3 non convoqué du joueur')
            ->assertSet(
                'licensees',
                fn ($licensees) => $licensees->first()->members->modelKeys() === [$calledMember->id]
            );
    }

    public function test_it_only_dispatches_a_pending_game_notification_once_per_session(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PLAYER]);
        $licensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
        ]);
        $user->licensees()->attach($licensee);
        $member = $licensee->members()->create([
            'type' => Member::TYPE_PLAYER,
        ]);

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
