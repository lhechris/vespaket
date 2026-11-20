<?php

namespace Tests\Feature;

use App\Livewire\Licensee\Form;
use App\Livewire\Licensee\Index;
use App\Livewire\Team\ManageMembers;
use App\Models\Game;
use App\Models\Licensee;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LicenseeTest extends TestCase
{
    use RefreshDatabase;

    public function test_licensee_form_creates_and_updates_a_licensee(): void
    {
        Livewire::test(Form::class)
            ->set('firstname', 'Camille')
            ->set('name', 'Martin')
            ->set('licence', 'ABC123')
            ->call('save')
            ->assertHasNoErrors();

        $licensee = Licensee::query()->firstOrFail();
        $this->assertSame('ABC123', $licensee->licence);

        Livewire::test(Form::class, ['licensee' => $licensee])
            ->set('firstname', 'Camille')
            ->set('name', 'Durand')
            ->set('licence', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('licensees', [
            'id' => $licensee->id,
            'name' => 'Durand',
            'licence' => null,
        ]);
    }

    public function test_licensee_index_searches_and_deletes_licensees(): void
    {
        $matching = Licensee::create([
            'firstname' => 'Camille',
            'name' => 'Martin',
            'licence' => 'ABC123',
        ]);
        Licensee::create([
            'firstname' => 'Alex',
            'name' => 'Durand',
            'licence' => 'XYZ789',
        ]);

        Livewire::test(Index::class)
            ->set('search', 'ABC123')
            ->assertSee('Camille Martin')
            ->assertDontSee('Alex Durand')
            ->call('delete', $matching->id)
            ->assertDontSee('Camille Martin');

        $this->assertDatabaseMissing('licensees', ['id' => $matching->id]);
    }

    public function test_licensee_index_displays_ten_results_per_page(): void
    {
        foreach (range(0, 11) as $number) {
            Licensee::create([
                'firstname' => sprintf('First %02d', $number),
                'name' => 'Member',
                'licence' => null,
            ]);
        }

        Livewire::test(Index::class)
            ->assertSet('licensees', fn ($licensees) => $licensees->count() === 10)
            ->assertSee('First 00 Member')
            ->assertDontSee('First 10 Member')
            ->assertSee('1 / 2')
            ->call('nextLicenseesPage')
            ->assertSet('licenseesPage', 2)
            ->assertSet('licensees', fn ($licensees) => $licensees->count() === 2)
            ->assertSee('First 10 Member')
            ->assertDontSee('First 00 Member')
            ->call('nextLicenseesPage')
            ->assertSet('licenseesPage', 2)
            ->call('previousLicenseesPage')
            ->assertSet('licenseesPage', 1)
            ->call('previousLicenseesPage')
            ->assertSet('licenseesPage', 1);
    }

    public function test_licensee_index_displays_attached_members_and_their_teams(): void
    {
        $licensee = Licensee::create([
            'firstname' => 'Camille',
            'name' => 'Martin',
            'licence' => null,
        ]);
        $team = Team::factory()->create(['name' => 'Équipe U15']);
        $member = $licensee->members()->create([
            'type' => Member::TYPE_PLAYER,
            'team_id' => $team->id,
        ]);

        Livewire::test(Index::class)
            ->assertSee(__('vespaket.player'))
            ->assertSee('Équipe U15')
            ->assertSet('licensees', fn ($licensees) => $licensees->first()->relationLoaded('members')
                && $licensees->first()->members->first()->relationLoaded('team'));
    }

    public function test_licensee_form_assigns_roles_to_teams_through_members(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COACH]);
        $teamA = Team::factory()->create(['name' => 'Équipe A']);
        $teamB = Team::factory()->create(['name' => 'Équipe B']);
        $teamC = Team::factory()->create(['name' => 'Équipe C']);
        $teamA->owners()->attach($user);
        $teamB->owners()->attach($user);

        Livewire::actingAs($user)
            ->test(Form::class)
            ->set('firstname', 'Camille')
            ->set('name', 'Martin')
            ->call('addTeamAssignment')
            ->assertSee('Équipe A')
            ->assertSee('Équipe B')
            ->assertDontSee('Équipe C')
            ->set('teamAssignments.0.team_id', (string) $teamA->id)
            ->set('teamAssignments.0.roles.player', true)
            ->call('addTeamAssignment')
            ->set('teamAssignments.1.team_id', (string) $teamB->id)
            ->set('teamAssignments.1.roles.coach', true)
            ->call('save')
            ->assertHasNoErrors();

        $licensee = Licensee::query()->firstOrFail();
        $this->assertDatabaseHas('members', [
            'licensee_id' => $licensee->id,
            'type' => Member::TYPE_PLAYER,
        ]);
        $this->assertDatabaseHas('members', [
            'licensee_id' => $licensee->id,
            'type' => Member::TYPE_COACH,
        ]);
        $this->assertTrue($teamA->members()->where('type', Member::TYPE_PLAYER)->exists());
        $this->assertTrue($teamB->members()->where('type', Member::TYPE_COACH)->exists());
        $this->assertFalse($teamC->members()->exists());

        Livewire::actingAs($user)
            ->test(Form::class, ['licensee' => $licensee])
            ->assertSet('teamAssignments.0.roles.player', true)
            ->assertSet('teamAssignments.1.roles.coach', true)
            ->set('teamAssignments', [
                [
                    'team_id' => (string) $teamA->id,
                    'roles' => [
                        Member::TYPE_PLAYER => false,
                        Member::TYPE_COACH => true,
                        Member::TYPE_OTM => false,
                    ],
                ],
                [
                    'team_id' => (string) $teamB->id,
                    'roles' => [
                        Member::TYPE_PLAYER => true,
                        Member::TYPE_COACH => false,
                        Member::TYPE_OTM => false,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($teamA->members()->where('licensee_id', $licensee->id)->where('type', Member::TYPE_COACH)->exists());
        $this->assertTrue($teamB->members()->where('licensee_id', $licensee->id)->where('type', Member::TYPE_PLAYER)->exists());
        $this->assertFalse($teamA->members()->where('licensee_id', $licensee->id)->where('type', Member::TYPE_PLAYER)->exists());
        $this->assertFalse($teamB->members()->where('licensee_id', $licensee->id)->where('type', Member::TYPE_COACH)->exists());

        Livewire::actingAs($user)
            ->test(Form::class, ['licensee' => $licensee])
            ->assertSet('teamAssignments.0.team_id', (string) $teamA->id)
            ->call('removeTeamAssignment', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($teamA->members()->where('licensee_id', $licensee->id)->where('type', Member::TYPE_PLAYER)->exists());
    }

    public function test_licensee_form_assigns_and_removes_substitutes_from_a_team(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($user);
        $game = Game::factory()->create([
            'team_id' => $team->id,
            'date' => now()->addDay(),
        ]);

        Livewire::actingAs($user)
            ->test(Form::class)
            ->set('firstname', 'Camille')
            ->set('name', 'Martin')
            ->call('addTeamAssignment')
            ->assertSee(__('vespaket.sub'))
            ->set('teamAssignments.0.team_id', (string) $team->id)
            ->set('teamAssignments.0.roles.sub', true)
            ->call('save')
            ->assertHasNoErrors();

        $licensee = Licensee::query()->firstOrFail();
        $substitute = $licensee->members()->where('type', Member::TYPE_SUB)->firstOrFail();
        $this->assertSame($team->id, $substitute->team_id);
        $this->assertTrue($game->members()->whereKey($substitute->id)->exists());

        Livewire::actingAs($user)
            ->test(Form::class, ['licensee' => $licensee])
            ->assertSet('teamAssignments.0.roles.sub', true)
            ->set('teamAssignments.0.roles.sub', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('members', ['id' => $substitute->id]);
        $this->assertFalse($game->members()->whereKey($substitute->id)->exists());
    }

    public function test_team_member_search_uses_licensee_identity_and_adds_a_member_by_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_COACH]);
        $team = Team::factory()->create();
        $team->owners()->attach($user);
        $licensee = Licensee::create([
            'firstname' => 'Camille',
            'name' => 'Martin',
            'licence' => 'LIC-123',
        ]);

        Livewire::actingAs($user)
            ->test(ManageMembers::class, ['team' => $team])
            ->set('search', 'Camille')
            ->assertSee('Camille Martin')
            ->assertSee('LIC-123')
            ->call('addLicenseeMember', $licensee->id, Member::TYPE_PLAYER)
            ->assertSet('results.0.id', $licensee->id)
            ->assertSee('Camille Martin');

        $member = $licensee->members()->where('type', Member::TYPE_PLAYER)->firstOrFail();
        $this->assertTrue($member->licensee->is($licensee));
        $this->assertTrue($team->members()->whereKey($member->id)->exists());
    }
}
