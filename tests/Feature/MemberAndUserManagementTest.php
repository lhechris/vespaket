<?php

namespace Tests\Feature;

use App\Livewire\Member\Manage as MemberManage;
use App\Livewire\User\Manage as UserManage;
use App\Models\Member;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MemberAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_a_member(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);

        Livewire::actingAs($coach)
            ->test(MemberManage::class)
            ->set('newMember', [
                'name' => 'Dupont',
                'prenom' => 'Alice',
                'type' => Member::TYPE_PLAYER,
                'licence' => 'LIC-123',
                'numero' => 7,
            ])
            ->call('createMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('members', [
            'name' => 'Dupont',
            'prenom' => 'Alice',
            'type' => Member::TYPE_PLAYER,
            'numero' => 7,
        ]);
    }

    public function test_coach_can_create_a_user_with_a_member_relation(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $member = Member::factory()->create();

        Livewire::actingAs($coach)
            ->test(UserManage::class)
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'alice@example.test')
            ->set('newUser.password', 'secret123')
            ->set('newUser.role', User::ROLE_PARENT)
            ->set("newUser.selectedMembers.{$member->id}", 'parent')
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'alice@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertDatabaseHas('member_user', [
            'user_id' => $user->id,
            'member_id' => $member->id,
            'relation' => 'parent',
        ]);
    }

    public function test_user_creation_requires_a_unique_email_and_password(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        User::factory()->create(['email' => 'existing@example.test']);

        Livewire::actingAs($coach)
            ->test(UserManage::class)
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'existing@example.test')
            ->set('newUser.password', 'short')
            ->call('createUser')
            ->assertHasErrors(['newUser.email', 'newUser.password']);
    }

    public function test_coach_can_create_an_invitation_from_the_user_form(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);

        Livewire::actingAs($coach)
            ->test(UserManage::class)
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'alice@example.test')
            ->set('newUser.role', User::ROLE_PARENT)
            ->call('invit')
            ->assertHasNoErrors()
            ->assertSet('link', fn (?string $link): bool => str_starts_with($link ?? '', url('/invitation/')));

        $invitation = Invitation::where('email', 'alice@example.test')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $invitation->user_id,
            'firstname' => 'Alice',
            'name' => 'Dupont',
            'role' => User::ROLE_PARENT,
        ]);
    }

    public function test_member_selections_from_an_invitation_are_not_reused_for_the_next_invitation(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $firstMember = Member::factory()->create();
        $secondMember = Member::factory()->create();
        $form = Livewire::actingAs($coach)->test(UserManage::class);

        $form
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'alice@example.test')
            ->set('newUser.role', User::ROLE_PARENT)
            ->set("newUser.selectedMembers.{$firstMember->id}", 'parent')
            ->call('invit')
            ->call('startCreate')
            ->set('newUser.firstname', 'Bob')
            ->set('newUser.name', 'Martin')
            ->set('newUser.email', 'bob@example.test')
            ->set('newUser.role', User::ROLE_PARENT)
            ->set("newUser.selectedMembers.{$secondMember->id}", 'parent')
            ->call('invit')
            ->assertHasNoErrors();

        $secondUser = User::where('email', 'bob@example.test')->firstOrFail();

        $this->assertDatabaseHas('member_user', [
            'user_id' => $secondUser->id,
            'member_id' => $secondMember->id,
            'relation' => 'parent',
        ]);
        $this->assertDatabaseMissing('member_user', [
            'user_id' => $secondUser->id,
            'member_id' => $firstMember->id,
        ]);
    }
}