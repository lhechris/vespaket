<?php

namespace Tests\Feature;

use App\Livewire\Game\Show;
use App\Models\Game;
use App\Models\GameOption;
use App\Models\Licensee;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GameShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_sees_all_called_members_but_can_only_update_their_own_availability(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PARENT]);
        $licensee = Licensee::create(['firstname' => 'Alice', 'name' => 'Martin']);
        $user->licensees()->attach($licensee);
        $ownMember = $licensee->members()->create(['type' => Member::TYPE_PLAYER]);

        $otherLicensee = Licensee::create(['firstname' => 'Bob', 'name' => 'Dupont']);
        $otherMember = $otherLicensee->members()->create(['type' => Member::TYPE_PLAYER]);
        $notCalledLicensee = Licensee::create(['firstname' => 'Charlie', 'name' => 'Martin']);
        $notCalledMember = $notCalledLicensee->members()->create(['type' => Member::TYPE_PLAYER]);

        $game = Game::factory()->create(['team_id' => Team::factory()->create()->id]);
        $game->members()->attach($ownMember->id, ['selected' => true]);
        $game->members()->attach($otherMember->id, ['selected' => true]);
        $game->members()->attach($notCalledMember->id, ['selected' => true, 'called' => false]);
        $option = GameOption::create([
            'team_id' => $game->team_id,
            'name' => 'Commentaire',
            'type' => GameOption::TYPE_TEXT,
            'display' => GameOption::DISP_ALL_EDITABLE,
        ]);

        $component = Livewire::actingAs($user)
            ->test(Show::class, ['game' => $game])
            ->assertSee('Alice')
            ->assertSee('Bob')
            ->assertDontSee('Charlie')
            ->assertSet('members', fn ($members) => $members->modelKeys() === [$ownMember->id])
            ->assertSet('players', fn ($players) => $players->modelKeys() === [$ownMember->id, $otherMember->id]);

        $html = $component->html();
        $this->assertStringContainsString(
            'setGameOption('.$ownMember->id.', '.$option->id.', $event.target.value)',
            $html
        );
        $this->assertStringNotContainsString(
            'setGameOption('.$otherMember->id.', '.$option->id.', $event.target.value)',
            $html
        );

        $component->call('setAvailability', $ownMember->id, 'yes')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('game_member', [
            'game_id' => $game->id,
            'member_id' => $ownMember->id,
            'availability' => 'yes',
        ]);
    }

    public function test_parent_cannot_update_availability_for_an_unlinked_game_member(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PARENT]);
        $licensee = Licensee::create(['firstname' => 'Alice', 'name' => 'Martin']);
        $user->licensees()->attach($licensee);
        $ownMember = $licensee->members()->create(['type' => Member::TYPE_PLAYER]);
        $otherLicensee = Licensee::create(['firstname' => 'Bob', 'name' => 'Dupont']);
        $otherMember = $otherLicensee->members()->create(['type' => Member::TYPE_PLAYER]);

        $game = Game::factory()->create(['team_id' => Team::factory()->create()->id]);
        $game->members()->attach($ownMember->id);
        $game->members()->attach($otherMember->id);

        Livewire::actingAs($user)
            ->test(Show::class, ['game' => $game])
            ->call('setAvailability', $otherMember->id, 'yes')
            ->assertForbidden();

        $this->assertDatabaseHas('game_member', [
            'game_id' => $game->id,
            'member_id' => $otherMember->id,
            'availability' => null,
        ]);
    }

    public function test_parent_can_update_an_editable_game_option_for_their_member(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PARENT]);
        $licensee = Licensee::create(['firstname' => 'Alice', 'name' => 'Martin']);
        $user->licensees()->attach($licensee);
        $member = $licensee->members()->create(['type' => Member::TYPE_PLAYER]);

        $team = Team::factory()->create();
        $game = Game::factory()->create(['team_id' => $team->id]);
        $game->members()->attach($member->id);
        $option = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Commentaire',
            'type' => GameOption::TYPE_TEXT,
            'display' => GameOption::DISP_ALL_EDITABLE,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['game' => $game])
            ->call('setGameOption', $member->id, $option->id, 'Présent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('game_member_option', [
            'game_id' => $game->id,
            'member_id' => $member->id,
            'game_option_id' => $option->id,
            'value' => 'Présent',
        ]);
    }

    public function test_parent_cannot_update_game_options_for_an_unlinked_member(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PARENT]);
        $team = Team::factory()->create();
        $game = Game::factory()->create(['team_id' => $team->id]);
        $ownLicensee = Licensee::create(['firstname' => 'Alice', 'name' => 'Martin']);
        $user->licensees()->attach($ownLicensee);
        $ownMember = $ownLicensee->members()->create(['type' => Member::TYPE_PLAYER]);
        $otherLicensee = Licensee::create(['firstname' => 'Bob', 'name' => 'Dupont']);
        $otherMember = $otherLicensee->members()->create(['type' => Member::TYPE_PLAYER]);
        $game->members()->attach($ownMember->id);
        $game->members()->attach($otherMember->id);
        $option = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Commentaire',
            'type' => GameOption::TYPE_TEXT,
            'display' => GameOption::DISP_ALL_EDITABLE,
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['game' => $game])
            ->call('setGameOption', $otherMember->id, $option->id, 'Non autorisé')
            ->assertForbidden();

        $this->assertDatabaseMissing('game_member_option', [
            'game_id' => $game->id,
            'member_id' => $otherMember->id,
            'game_option_id' => $option->id,
        ]);
    }
}
