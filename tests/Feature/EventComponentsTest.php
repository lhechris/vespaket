<?php

namespace Tests\Feature;

use App\Livewire\Event\Card;
use App\Livewire\Event\Create;
use App\Livewire\Event\Edit;
use App\Livewire\Event\Show;
use App\Models\Event;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_event_and_team_players_are_attached(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $player = Member::factory()->create(['type' => Member::TYPE_PLAYER,'team_id' => $team->id]);
        $coachMember = Member::factory()->create(['type' => Member::TYPE_COACH,'team_id' => $team->id]);

        Livewire::actingAs($coach)
            ->test(Create::class, ['team' => $team])
            ->set('date', '2026-11-10T18:30')
            ->set('location', 'Gymnase central')
            ->set('titre', 'Tournoi')
            ->set('description', 'Tournoi régional')
            ->call('save')
            ->assertHasNoErrors();

        $event = Event::where('titre', 'Tournoi')->firstOrFail();
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'team_id' => $team->id,
            'date' => '2026-11-10T18:30',
            'location' => 'Gymnase central',
            'description' => 'Tournoi régional',
        ]);
        $this->assertDatabaseHas('event_member', [
            'event_id' => $event->id,
            'member_id' => $player->id,
        ]);
        $this->assertDatabaseMissing('event_member', [
            'event_id' => $event->id,
            'member_id' => $coachMember->id,
        ]);
    }

    public function test_event_creation_requires_date_location_and_title(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();

        Livewire::actingAs($coach)
            ->test(Create::class, ['team' => $team])
            ->call('save')
            ->assertHasErrors([
                'date' => 'required',
                'location' => 'required',
                'titre' => 'required',
            ]);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_non_coach_cannot_create_an_event(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $team = Team::factory()->create();

        Livewire::actingAs($parent)
            ->test(Create::class, ['team' => $team])
            ->set('date', '2026-11-10T18:30')
            ->set('location', 'Gymnase central')
            ->set('titre', 'Tournoi')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('events', 0);
    }

    public function test_coach_can_update_every_event_field(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $event = Event::create([
            'team_id' => $team->id,
            'titre' => 'Ancien titre',
            'date' => '2026-11-10 18:30:00',
            'location' => 'Ancien lieu',
            'description' => 'Ancienne description',
        ]);

        Livewire::actingAs($coach)
            ->test(Edit::class, ['event' => $event])
            ->set('eventTitle', 'Nouveau titre')
            ->set('eventDate', '2026-11-12T19:45')
            ->set('eventLocation', 'Nouveau lieu')
            ->set('eventDescription', 'Nouvelle description')
            ->call('updateEvent')
            ->assertSet('editingEvent', false);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'titre' => 'Nouveau titre',
            'date' => '2026-11-12T19:45',
            'location' => 'Nouveau lieu',
            'description' => 'Nouvelle description',
        ]);
    }

    public function test_non_coach_cannot_update_an_event(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $team = Team::factory()->create();
        $event = Event::create([
            'team_id' => $team->id,
            'titre' => 'Titre initial',
            'date' => '2026-11-10 18:30:00',
            'location' => 'Lieu initial',
            'description' => 'Description initiale',
        ]);

        Livewire::actingAs($parent)
            ->test(Edit::class, ['event' => $event])
            ->set('eventTitle', 'Titre modifié')
            ->call('updateEvent')
            ->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'titre' => 'Titre initial',
        ]);
    }

    public function test_toggle_editing_event_loads_current_values_when_editing_is_opened(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $event = Event::create([
            'team_id' => $team->id,
            'titre' => 'Tournoi régional',
            'date' => '2026-11-10 18:30:00',
            'location' => 'Gymnase central',
            'description' => 'Rendez-vous à 18h',
        ]);

        Livewire::actingAs($coach)
            ->test(Edit::class, ['event' => $event])
            ->call('toggleEditingEvent')
            ->assertSet('editingEvent', true)
            ->assertSet('eventTitle', 'Tournoi régional')
            ->assertSet('eventDate', '2026-11-10 18:30:00')
            ->assertSet('eventLocation', 'Gymnase central')
            ->assertSet('eventDescription', 'Rendez-vous à 18h')
            ->set('eventTitle', 'Modification temporaire')
            ->call('toggleEditingEvent')
            ->assertSet('editingEvent', false)
            ->call('toggleEditingEvent')
            ->assertSet('editingEvent', true)
            ->assertSet('eventTitle', 'Tournoi régional');
    }

    public function test_edit_component_set_availability_updates_event_member_pivot(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        [$event, $member] = $this->createEventMember();

        Livewire::actingAs($coach)
            ->test(Edit::class, ['event' => $event])
            ->call('setAvailability', $member->id, 'maybe');

        $this->assertDatabaseHas('event_member', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'availability' => 'maybe',
        ]);
    }

    public function test_delete_event_removes_event_and_redirects_to_its_team(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $event = Event::create([
            'team_id' => $team->id,
            'titre' => 'À supprimer',
            'date' => '2026-11-10 18:30:00',
            'location' => 'Gymnase',
            'description' => 'Événement de test',
        ]);

        $response = Livewire::actingAs($coach)
            ->test(Edit::class, ['event' => $event])
            ->call('deleteEvent');

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
        $this->assertSame(route('team.show', [$team->id]), $response->effects['redirect']);
    }

    public function test_coach_can_set_availability_for_an_event_member(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        [$event, $member] = $this->createEventMember();

        Livewire::actingAs($coach)
            ->test(Card::class, ['event' => $event, 'member' => $member])
            ->call('setAvailability', 'yes')
            ->assertSet('availability', 'yes');

        $this->assertDatabaseHas('event_member', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'availability' => 'yes',
        ]);
    }

    public function test_parent_can_set_availability_only_for_their_associated_member(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        [$event, $child] = $this->createEventMember();
        $otherMember = Member::factory()->create();
        $event->members()->attach($otherMember->id);
        $parent->licensees()->attach($child->licensee);

        Livewire::actingAs($parent)
            ->test(Card::class, ['event' => $event, 'member' => $child])
            ->call('setAvailability', 'yes')
            ->assertSet('availability', 'yes');

        $this->assertDatabaseHas('event_member', [
            'event_id' => $event->id,
            'member_id' => $child->id,
            'availability' => 'yes',
        ]);

        Livewire::actingAs($parent)
            ->test(Card::class, ['event' => $event, 'member' => $otherMember])
            ->call('setAvailability', 'no')
            ->assertForbidden();

        $this->assertDatabaseMissing('event_member', [
            'event_id' => $event->id,
            'member_id' => $otherMember->id,
            'availability' => 'no',
        ]);
    }

    public function test_user_who_is_neither_coach_nor_parent_cannot_set_availability(): void
    {
        $playerUser = User::factory()->create(['role' => User::ROLE_PLAYER]);
        [$event, $member] = $this->createEventMember();

        Livewire::actingAs($playerUser)
            ->test(Card::class, ['event' => $event, 'member' => $member])
            ->call('setAvailability', 'yes')
            ->assertForbidden();

        $this->assertDatabaseHas('event_member', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'availability' => null,
        ]);
    }

    public function test_event_show_lists_all_players_but_only_parent_members_for_responses(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        [$event, $child] = $this->createEventMember();
        $otherMember = Member::factory()->create();
        $otherMember->licensee->update(['firstname' => 'Autre joueur']);
        $event->members()->attach($otherMember->id);
        $parent->licensees()->attach($child->licensee);

        Livewire::actingAs($parent)
            ->test(Show::class, ['event' => $event])
            ->assertSee($child->licensee->firstname)
            ->assertSee('Autre joueur')
            ->assertSet('members', fn ($members): bool => $members->modelKeys() === [$child->id])
            ->assertSet('players', fn ($players): bool => $players->modelKeys() === [$child->id, $otherMember->id]);
    }

    public function test_event_show_parent_can_update_availability_for_linked_member(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        [$event, $child] = $this->createEventMember();
        $parent->licensees()->attach($child->licensee);

        Livewire::actingAs($parent)
            ->test(Show::class, ['event' => $event])
            ->call('setAvailability', $child->id, 'yes')
            ->assertSet('members.0.pivot.availability', 'yes');

        $this->assertDatabaseHas('event_member', [
            'event_id' => $event->id,
            'member_id' => $child->id,
            'availability' => 'yes',
        ]);
    }

    public function test_event_show_parent_cannot_update_availability_for_unlinked_member(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        [$event, $child] = $this->createEventMember();
        $otherMember = Member::factory()->create();
        $event->members()->attach($otherMember->id);
        $parent->licensees()->attach($child->licensee);

        Livewire::actingAs($parent)
            ->test(Show::class, ['event' => $event])
            ->call('setAvailability', $otherMember->id, 'no')
            ->assertForbidden();

        $this->assertDatabaseHas('event_member', [
            'event_id' => $event->id,
            'member_id' => $otherMember->id,
            'availability' => null,
        ]);
    }

    private function createEventMember(): array
    {
        $team = Team::factory()->create();
        $event = Event::create([
            'team_id' => $team->id,
            'titre' => 'Match amical',
            'date' => '2026-11-10 18:30:00',
            'location' => 'Gymnase',
            'description' => 'Description',
        ]);
        $member = Member::factory()->create();
        $event->members()->attach($member->id);

        return [$event, $member];
    }
}