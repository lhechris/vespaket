<?php

namespace Tests\Feature;

use App\Livewire\Training\Create;
use App\Livewire\Training\Show;
use App\Models\Member;
use App\Models\Team;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_training_and_team_players_are_attached(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $player = Member::factory()->create(['type' => Member::TYPE_PLAYER, "team_id" => $team->id]);
        $teamCoach = Member::factory()->create(['type' => Member::TYPE_COACH, "team_id" => $team->id]);

        $response = Livewire::actingAs($coach)
            ->test(Create::class, ['team' => $team])
            ->set('date', '2026-11-10')
            ->set('titre', 'Entraînement collectif')
            ->set('location', 'Gymnase central')
            ->call('save')
            ->assertHasNoErrors();

        $training = Training::where('titre', 'Entraînement collectif')->firstOrFail();
        $this->assertSame(route('training.show', ['training' => $training->id]), $response->effects['redirect']);
        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'team_id' => $team->id,
            'date' => '2026-11-10',
            'titre' => 'Entraînement collectif',
            'location' => 'Gymnase central',
        ]);
        $this->assertDatabaseHas('member_training', [
            'training_id' => $training->id,
            'member_id' => $player->id,
        ]);
        $this->assertDatabaseMissing('member_training', [
            'training_id' => $training->id,
            'member_id' => $teamCoach->id,
        ]);
    }

    public function test_non_coach_cannot_create_training(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $team = Team::factory()->create();

        Livewire::actingAs($parent)
            ->test(Create::class, ['team' => $team])
            ->set('date', '2026-11-10')
            ->set('titre', 'Entraînement collectif')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('trainings', 0);
    }

    public function test_coach_can_update_training_title_date_and_location(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $training = Training::create([
            'team_id' => $team->id,
            'titre' => 'Ancien entraînement',
            'date' => '2026-11-10 18:00:00',
            'location' => 'Ancien gymnase',
        ]);

        Livewire::actingAs($coach)
            ->test(Show::class, ['training' => $training])
            ->set('trainingTitle', 'Nouvel entraînement')
            ->set('trainingDate', '2026-11-12')
            ->set('trainingLocation', 'Nouveau gymnase')
            ->call('updateTraining')
            ->assertSet('editingTraining', false);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'titre' => 'Nouvel entraînement',
            'date' => '2026-11-12',
            'location' => 'Nouveau gymnase',
        ]);
    }

    public function test_coach_can_update_member_presence(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        [$training, $member] = $this->createTrainingWithMember();

        Livewire::actingAs($coach)
            ->test(Show::class, ['training' => $training])
            ->call('setPresence', $member->id, 'yes')
            ->assertSet("presence.{$member->id}", 'yes');

        $this->assertDatabaseHas('member_training', [
            'training_id' => $training->id,
            'member_id' => $member->id,
            'present' => 'yes',
        ]);
    }

    public function test_coach_can_delete_training_and_is_redirected_to_team(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $training = Training::create([
            'team_id' => $team->id,
            'titre' => 'À supprimer',
            'date' => '2026-11-10 18:00:00',
            'location' => 'Gymnase',
        ]);

        $response = Livewire::actingAs($coach)
            ->test(Show::class, ['training' => $training])
            ->call('deleteTraining');

        $this->assertDatabaseMissing('trainings', ['id' => $training->id]);
        $this->assertSame(route('team.show', [$team->id]), $response->effects['redirect']);
    }

    private function createTrainingWithMember(): array
    {
        $team = Team::factory()->create();
        $member = Member::factory()->create(['type' => Member::TYPE_PLAYER,'team_id' => $team->id]);
        $training = Training::create([
            'team_id' => $team->id,
            'titre' => 'Entraînement',
            'date' => '2026-11-10 18:00:00',
            'location' => 'Gymnase',
        ]);
        $training->members()->attach($member->id, ['present' => 'maybe']);

        return [$training, $member];
    }
}