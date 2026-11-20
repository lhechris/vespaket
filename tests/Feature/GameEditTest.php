<?php

namespace Tests\Feature;

use App\Livewire\Game\Edit;
use App\Livewire\Team\ManageMembers;
use App\Models\Game;
use App\Models\GameMemberOption;
use App\Models\GameOption;
use App\Models\Member;
use App\Models\Place;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

use Illuminate\Support\Facades\DB;

class GameEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_mount_initializes_an_empty_number_option(): void
    {
        [$team, $game, $member, $option] = $this->createGameWithNumberOption();

        GameMemberOption::create([
            'game_id' => $game->id,
            'member_id' => $member->id,
            'game_option_id' => $option->id,
            'value' => null,
        ]);

        Livewire::test(Edit::class, ['game' => $game]);

        $this->assertDatabaseHas('game_member_option', [
            'game_id' => $game->id,
            'member_id' => $member->id,
            'game_option_id' => $option->id,
            'value' => (string) $member->numero,
        ]);
    }

    public function test_mount_initializes_without_number_option(): void
    {
       /*DB::listen(function ($query) {        
        \Log::debug($query->sql);
        \Log::debug($query->bindings);
        });*/

        [$team, $game, $member, $option] = $this->createGameWithNumberOption();
      
        Livewire::test(Edit::class, ['game' => $game]);

        $this->assertDatabaseHas('game_member_option', [
            'game_id' => $game->id,
            'member_id' => $member->id,
            'game_option_id' => $option->id,
            'value' => (string) $member->numero,
        ]);
    }

    public function test_set_game_option_updates_the_member_option(): void
    {
        [$team, $game, $member, $option] = $this->createGameWithNumberOption();

        GameMemberOption::create([
            'game_id' => $game->id,
            'member_id' => $member->id,
            'game_option_id' => $option->id,
            'value' => '7',
        ]);

        Livewire::test(Edit::class, ['game' => $game])
            ->call('setGameOption', $member->id, $option->id, '12');

        $this->assertDatabaseHas('game_member_option', [
            'game_id' => $game->id,
            'member_id' => $member->id,
            'game_option_id' => $option->id,
            'value' => '12',
        ]);

        $game2 = Game::factory()->create(['team_id' => $team->id]);
        $game2->members()->attach($member->id, [
            'selected' => true,
        ]);
        Livewire::test(Edit::class, ['game' => $game2]);
        $this->assertDatabaseHas('game_member_option', [
            'game_id' => $game2->id,
            'member_id' => $member->id,
            'game_option_id' => $option->id,
            'value' => (string) $member->numero,
        ]);

    }

    public function test_add_member_does_not_duplicate_game_pivot_rows(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->owners()->attach($user->id);
        $member = Member::factory()->create();
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'date' => now()->addDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(ManageMembers::class, ['team' => $team])
            ->call('addMember', $member->id)
            ->call('addMember', $member->id);

        $this->assertDatabaseCount('game_member', 1);
        $this->assertDatabaseHas('game_member', [
            'game_id' => $game->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_update_game_persists_the_form_values(): void
    {
        $team = Team::factory()->create();
        $place = Place::create([
            'name' => 'Complexe sportif',
            'address' => '1 rue des tests',
            'lat' => 48.8566,
            'lng' => 2.3522,
        ]);
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'titre' => 'Ancien titre',
            'date' => '2026-09-15 18:30:00',
            'location' => 'Ancien lieu',
            'rendezvous' => '17h30',
            'score' => '0-0',
            'commentaire' => 'Ancien commentaire',
            'numero' => 1,
        ]);

        Livewire::test(Edit::class, ['game' => $game])
            ->set('gameTitle', 'Nouveau titre')
            ->set('gameDate', '2026-10-10T20:00')
            ->set('gamePlaceId', $place->id)
            ->set('gameRendezvous', '19h00')
            ->set('gameScore', '2-1')
            ->set('gameCommentaire', 'Commentaire mis à jour')
            ->set('gameNumero', 12)
            ->call('updateGame')
            ->assertSet('editingGame', false);

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'titre' => 'Nouveau titre',
            'date' => '2026-10-10T20:00',
            'location' => $place->name,
            'place_id' => $place->id,
            'rendezvous' => '19h00',
            'score' => '2-1',
            'commentaire' => 'Commentaire mis à jour',
            'numero' => 12,
        ]);
    }

    public function test_toggle_editing_game_reloads_the_form_values(): void
    {
        $team = Team::factory()->create();
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'titre' => 'Match de préparation',
            'date' => '2026-10-20 18:00:00',
            'rendezvous' => '17h15',
            'score' => '1-1',
            'commentaire' => 'À confirmer',
            'numero' => 7,
        ]);

        Livewire::test(Edit::class, ['game' => $game])
            ->call('toggleEditingGame')
            ->assertSet('editingGame', true)
            ->assertSet('gameTitle', 'Match de préparation')
            ->assertSet('gameDate', '2026-10-20 18:00:00')
            ->assertSet('gameRendezvous', '17h15')
            ->assertSet('gameScore', '1-1')
            ->assertSet('gameCommentaire', 'À confirmer')
            ->assertSet('gameNumero', 7);
    }

    public function test_set_availability_updates_the_game_member_pivot(): void
    {
        $team = Team::factory()->create();
        $game = Game::factory()->create(['team_id' => $team->id]);
        $member = Member::factory()->create();
        $game->members()->attach($member->id, [
            'selected' => true,
            'availability' => 'no',
        ]);

        Livewire::test(Edit::class, ['game' => $game])
            ->call('setAvailability', $member->id, 'yes');

        $this->assertDatabaseHas('game_member', [
            'game_id' => $game->id,
            'member_id' => $member->id,
            'availability' => 'yes',
        ]);
    }

    public function test_toggle_selection_updates_selected_status(): void
    {
        $team = Team::factory()->create();
        $game = Game::factory()->create(['team_id' => $team->id]);
        $member = Member::factory()->create();
        $game->members()->attach($member->id, [
            'selected' => false,
            'availability' => 'yes',
        ]);

        Livewire::test(Edit::class, ['game' => $game])
            ->call('toggleSelection', $member->id);

        $this->assertDatabaseHas('game_member', [
            'game_id' => $game->id,
            'member_id' => $member->id,
            'selected' => true,
        ]);
    }

    public function test_copy_and_open_whatsapp_dispatches_the_message(): void
    {
        $team = Team::factory()->create([
            'whatsapp' => 'https://wa.me/123456789',
            'msg_convocation' => 'Bonjour %SELECTION%',
        ]);
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'date' => '2026-11-12 18:00:00',
            'rendezvous' => '17h30',
        ]);

        $selectedMember = Member::factory()->create();
        $selectedMember->licensee->update(['firstname' => 'Alice']);
        $unselectedMember = Member::factory()->create();
        $unselectedMember->licensee->update(['firstname' => 'Bob']);

        $game->members()->attach($selectedMember->id, ['selected' => true, 'availability' => 'yes']);
        $game->members()->attach($unselectedMember->id, ['selected' => false, 'availability' => 'no']);

        Livewire::test(Edit::class, ['game' => $game])
            ->call('copyAndOpenWhatsapp')
            ->assertSet('message', 'Bonjour Alice')
            ->assertDispatched('copy-and-open-whatsapp');
    }

    public function test_delete_game_removes_the_match_and_redirects_to_team(): void
    {
        $team = Team::factory()->create();
        $game = Game::factory()->create(['team_id' => $team->id]);

        $response = Livewire::test(Edit::class, ['game' => $game])
            ->call('deleteGame');

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
        $this->assertArrayHasKey('redirect', $response->effects);
        $this->assertSame(route('team.show', ['team' => $team->id]), $response->effects['redirect']);
    }

    public function test_generate_feuille_handles_nullable_rendezvous_without_deprecation(): void
    {
        $team = Team::factory()->create([
            'name' => 'U11 Test',
            'msg_convocation' => 'Rdv %RENDEZVOUS% pour %SELECTION%',
        ]);
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'numero' => 12,
            'date' => '2026-11-12 18:00:00',
            'rendezvous' => null,
        ]);

        $coach = Member::factory()->create(['type' => 'coach','team_id' => $team->id]);
        $coach->licensee->update(['firstname' => 'Coach', 'name' => 'C', 'licence' => 'C1']);

        $selectedMember = Member::factory()->create(['type' => 'player', 'numero' => 7,'team_id' => $team->id]);
        $selectedMember->licensee->update(['firstname' => 'Alice', 'name' => 'A', 'licence' => 'P1']);
        $game->members()->attach($selectedMember->id, ['selected' => true, 'availability' => 'yes']);

        $oppositionOption = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Opposition',
            'display' => GameOption::DISP_ALL,
            'type' => GameOption::TYPE_OPPOSITION,
            'order' => 1,
        ]);
        GameMemberOption::create([
            'game_id' => $game->id,
            'member_id' => $selectedMember->id,
            'game_option_id' => $oppositionOption->id,
            'value' => 'A',
        ]);

        $numberOption = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Numero',
            'display' => GameOption::DISP_ALL,
            'type' => GameOption::TYPE_NUM,
            'order' => 2,
        ]);
        GameMemberOption::create([
            'game_id' => $game->id,
            'member_id' => $selectedMember->id,
            'game_option_id' => $numberOption->id,
            'value' => '7',
        ]);

        Livewire::test(Edit::class, ['game' => $game])
            ->call('generateFeuille')
            ->assertSet('message', 'Rdv  pour Alice');
    }

    public function test_generate_feuille_uses_game_option_number_before_member_number(): void
    {
        $team = Team::factory()->create(['name' => 'U11 Test']);
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'numero' => 12,
            'date' => '2026-11-12 18:00:00',
            'rendezvous' => '17h30',
        ]);

        $player = Member::factory()->create([
            'type' => 'player',
            'numero' => 99,
            'team_id' => $team->id
        ]);
        $player->licensee->update(['firstname' => 'Alice', 'name' => 'A', 'licence' => 'P1']);
        $game->members()->attach($player->id, ['selected' => true, 'availability' => 'yes']);

        $oppositionOption = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Opposition',
            'display' => GameOption::DISP_ALL,
            'type' => GameOption::TYPE_OPPOSITION,
            'order' => 1,
        ]);
        GameMemberOption::create([
            'game_id' => $game->id,
            'member_id' => $player->id,
            'game_option_id' => $oppositionOption->id,
            'value' => 'A',
        ]);

        $numberOption = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Numero',
            'display' => GameOption::DISP_ALL,
            'type' => GameOption::TYPE_NUM,
            'order' => 2,
        ]);
        GameMemberOption::create([
            'game_id' => $game->id,
            'member_id' => $player->id,
            'game_option_id' => $numberOption->id,
            'value' => '7',
        ]);

        $spreadsheet = $this->loadGeneratedFeuilleSpreadsheet($game);

        $this->assertSame('7', (string) $spreadsheet->getActiveSheet()->getCell('A18')->getValue());
    }

    public function test_generate_feuille_falls_back_to_member_number_when_no_game_option_number_exists(): void
    {
        $team = Team::factory()->create(['name' => 'U11 Test']);
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'numero' => 12,
            'date' => '2026-11-12 18:00:00',
            'rendezvous' => '17h30',
        ]);

        $player = Member::factory()->create([
            'type' => 'player',
            'numero' => 21,
            'team_id' => $team->id
        ]);
        $player->licensee->update(['firstname' => 'Bob', 'name' => 'B', 'licence' => 'P2']);
        $game->members()->attach($player->id, ['selected' => true, 'availability' => 'yes']);

        $oppositionOption = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Opposition',
            'display' => GameOption::DISP_ALL,
            'type' => GameOption::TYPE_OPPOSITION,
            'order' => 1,
        ]);
        GameMemberOption::create([
            'game_id' => $game->id,
            'member_id' => $player->id,
            'game_option_id' => $oppositionOption->id,
            'value' => 'A',
        ]);

        $spreadsheet = $this->loadGeneratedFeuilleSpreadsheet($game);

        $this->assertSame('21', (string) $spreadsheet->getActiveSheet()->getCell('A18')->getValue());
    }

    private function loadGeneratedFeuilleSpreadsheet(Game $game): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $component = new Edit();
        $component->game = $game;
        $component->mount();

        $response = $component->generateFeuille();
        ob_start();
        $response->getCallback()();
        $xlsxContents = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'sheet_');
        file_put_contents($path, $xlsxContents);

        try {
            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    private function createGameWithNumberOption(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $member = Member::factory()->create(['numero' => 10]);
        $game = Game::factory()->create(['team_id' => $team->id]);
        
        $game->members()->attach($member->id, [
            'selected' => true,
        ]);

        $option = GameOption::create([
            'team_id' => $team->id,
            'name' => 'Numero',
            'display' => GameOption::DISP_ALL,
            'type' => GameOption::TYPE_NUM,
            'order' => 1,
        ]);

        return [$team, $game, $member, $option];
    }
}
