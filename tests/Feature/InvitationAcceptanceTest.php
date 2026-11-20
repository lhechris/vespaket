<?php

namespace Tests\Feature;

use App\Livewire\User\Accept;
use App\Models\Invitation;
use App\Models\Licensee;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_user_can_complete_account_without_changing_attached_licensees(): void
    {
        [$invitation, $invitedUser, $licensee, $otherLicensee] = $this->createInvitationWithLicensees();

        Livewire::test(Accept::class, ['token' => $invitation->token])
            ->set('firstname', 'Alice')
            ->set('name', 'Dupont')
            ->set('email', 'parent@example.test')
            ->set('password', 'StrongPassword123!')
            ->set('password_confirmation', 'StrongPassword123!')
            ->call('store')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $invitedUser->refresh();

        $this->assertSame('Alice', $invitedUser->firstname);
        $this->assertSame('Dupont', $invitedUser->name);
        $this->assertSame('parent@example.test', $invitedUser->email);
        $this->assertTrue(Hash::check('StrongPassword123!', $invitedUser->password));
        $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
        $this->assertDatabaseHas('licensee_user', [
            'user_id' => $invitedUser->id,
            'licensee_id' => $licensee->id,
        ]);
        $this->assertDatabaseMissing('licensee_user', [
            'user_id' => $invitedUser->id,
            'licensee_id' => $otherLicensee->id,
        ]);
        $this->assertDatabaseHas('members', [
            'licensee_id' => $licensee->id,
            'type' => Member::TYPE_PLAYER,
        ]);
        $this->assertAuthenticatedAs($invitedUser);
    }

    public function test_invitation_acceptance_requires_registration_fields(): void
    {
        [$invitation] = $this->createInvitationWithLicensees();

        Livewire::test(Accept::class, ['token' => $invitation->token])
            ->call('store')
            ->assertHasErrors([
                'firstname' => 'required',
                'name' => 'required',
                'email' => 'required',
                'password' => 'required',
            ]);

        $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
    }

    private function createInvitationWithLicensees(): array
    {
        $creator = User::factory()->create(['role' => User::ROLE_COACH]);
        $invitedUser = User::factory()->create([
            'firstname' => 'À compléter',
            'name' => 'À compléter',
            'email' => 'invited@example.test',
            'role' => User::ROLE_PARENT,
        ]);
        $licensee = Licensee::create([
            'firstname' => 'Enfant lié',
            'name' => 'Dupont',
        ]);
        $otherLicensee = Licensee::create([
            'firstname' => 'Autre enfant',
            'name' => 'Martin',
        ]);
        $licensee->members()->create(['type' => Member::TYPE_PLAYER]);
        $otherLicensee->members()->create(['type' => Member::TYPE_PLAYER]);
        $invitedUser->licensees()->attach($licensee);

        $invitation = Invitation::create([
            'email' => 'invited@example.test',
            'token' => 'acceptance-test-token-'.$invitedUser->id,
            'created_by' => $creator->id,
            'user_id' => $invitedUser->id,
            'expires_at' => now()->addDays(7),
        ]);

        return [$invitation, $invitedUser, $licensee, $otherLicensee];
    }
}