<?php

namespace Tests\Feature;

use App\Livewire\Team\Games;
use App\Livewire\Team\Members;
use App\Livewire\Team\Parameters;
use App\Livewire\Team\Stats;
use App\Models\Game;
use App\Models\GameMemberOption;
use App\Models\GameOption;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamSubcomponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_lists_players_and_aggregates_game_options(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $team = Team::factory()->create();
        $team->owners()->attach($owner->id);
        $player = Member::factory()->create([
            'team_id' => $team->id,
        ]);
        $player->licensee->update([
            'firstname' => 'Alice',
            'name' => 'Example',
        ]);

        $recentGame = Game::factory()->create([
            'team_id' => $team->id,
            'date' => now()->addDay(),
        ]);
        $earlierGame = Game::factory()->create([
            'team_id' => $team->id,
            'date' => now(),
        ]);
        $oldestGame = Game::factory()->create([
            'team_id' => $team->id,
            'date' => now()->subDay(),
        ]);
        $points = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Points',
            'type' => GameOption::TYPE_NUM,
            'display' => GameOption::DISP_STAT,
            'order' => 1,
        ]);
        $note = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Note',
            'type' => GameOption::TYPE_TEXT,
            'display' => GameOption::DISP_STAT,
            'order' => 2,
        ]);
        $checkIns = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Présence',
            'type' => GameOption::TYPE_CHECKBOX,
            'display' => GameOption::DISP_STAT,
            'order' => 3,
        ]);

        foreach ([
            [$recentGame, $points, '7'],
            [$earlierGame, $points, '4'],
            [$recentGame, $note, 'Récent'],
            [$earlierGame, $note, 'Ancien'],
            [$recentGame, $checkIns, 'yes'],
            [$earlierGame, $checkIns, 'no'],
            [$oldestGame, $checkIns, 'true'],
        ] as [$game, $option, $value]) {
            GameMemberOption::create([
                'game_id' => $game->id,
                'member_id' => $player->id,
                'game_option_id' => $option->id,
                'value' => $value,
            ]);
        }

        Livewire::test(Stats::class, ['team' => $team])
            ->assertSee('Points')
            ->assertSee('Note')
            ->assertSee('Présence')
            ->assertSeeInOrder(['Alice Example', '11', 'Ancien, Récent', '2']);
    }

    public function test_members_lists_players_and_saves_member_edits(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);
        $player = Member::factory()->create(['numero' => 8,'team_id' => $team->id]);
        $player->licensee->update(['firstname' => 'Alice', 'licence' => 'LIC-1']);
        $coach = Member::factory()->create(['type' => Member::TYPE_COACH ,  'team_id' => $team->id]);
        $coach->licensee->update(['firstname' => 'Coach']);

        Livewire::actingAs($owner)
            ->test(Members::class, ['team' => $team])
            ->assertSee('Alice')
            ->assertSet('players', fn ($players) => $players->modelKeys() === [$player->id])
            ->call('editMember', $player->id)
            ->assertSet('editingMemberId', $player->id)
            ->set('editingMember.prenom', 'Alice mise à jour')
            ->set('editingMember.licence', 'LIC-2')
            ->set('editingMember.numero', '12')
            ->call('saveMember')
            ->assertSet('editingMemberId', null);

        $this->assertDatabaseHas('licensees', [
            'id' => $player->licensee_id,
            'firstname' => 'Alice mise à jour',
            'licence' => 'LIC-2',
        ]);
        $this->assertDatabaseHas('members', [
            'id' => $player->id,
            'numero' => '12',
        ]);
    }

    public function test_games_lists_matches_and_identifies_the_next_game(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);
        Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 1',
            'team_away' => 'Equipe 2',
            'date' => now()->subDay(),
        ]);
        $nextGame = Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 3',
            'team_away' => 'Equipe 4',
            'date' => now()->addDay(),
        ]);
        Game::factory()->create([
            'team_id' => $team->id,
            'team_home' => 'Equipe 5',
            'team_away' => 'Equipe 6',
            'date' => now()->addDays(2),
        ]);

        Livewire::actingAs($owner)
            ->test(Games::class, ['team' => $team])
            ->assertSet('nextGameId', $nextGame->id)
            ->assertSee('Equipe 1')
            ->assertSee('Equipe 3')
            ->assertSee('Equipe 5');
    }

    public function test_parameters_saves_team_details_and_creates_game_options(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);

        Livewire::actingAs($owner)
            ->test(Parameters::class, ['team' => $team])
            ->set('teamName', 'Equipe modifiée')
            ->set('teamWhatsapp', 'https://wa.me/123456')
            ->set('teamMsgConvocation', 'Rendez-vous à 18h')
            ->set('gameoptions.new-option', [
                'id' => null,
                'name' => 'Numéro',
                'type' => GameOption::TYPE_NUM,
                'order' => 1,
                'display' => GameOption::DISP_ALL,
            ])
            ->call('saveAll')
            ->assertDispatched('saved');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Equipe modifiée',
            'whatsapp' => 'https://wa.me/123456',
            'msg_convocation' => 'Rendez-vous à 18h',
        ]);
        $this->assertDatabaseHas('game_options', [
            'team_id' => $team->id,
            'name' => 'Numéro',
            'type' => GameOption::TYPE_NUM,
            'order' => 1,
            'display' => GameOption::DISP_ALL,
        ]);
    }

    public function test_parameters_can_add_and_delete_game_option_rows(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($owner);
        $option = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Ancienne option',
            'type' => GameOption::TYPE_TEXT,
            'order' => 1,
            'display' => GameOption::DISP_ALL,
        ]);

        Livewire::actingAs($owner)
            ->test(Parameters::class, ['team' => $team])
            ->call('addRow')
            ->assertCount('gameoptions', 2)
            ->call('delete', $option->id);

        $this->assertDatabaseMissing('game_options', ['id' => $option->id]);
    }
}