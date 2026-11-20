<?php

namespace Tests\Feature;

use App\Livewire\User\Manage as UserManage;
use App\Models\Invitation;
use App\Models\Licensee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MemberAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_a_user_with_a_licensee_relation(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $licensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
            'licence' => 'LIC-123',
        ]);

        Livewire::actingAs($coach)
            ->test(UserManage::class)
            ->call('startCreate')
            ->set('licenseeSearch', 'LIC-123')
            ->assertSee('Alice Dupont')
            ->assertSee('LIC-123')
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'alice@example.test')
            ->set('newUser.password', 'secret123')
            ->set('newUser.role', User::ROLE_PARENT)
            ->set('newUser.selectedLicensees', [$licensee->id])
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'alice@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertDatabaseHas('licensee_user', [
            'user_id' => $user->id,
            'licensee_id' => $licensee->id,
        ]);
    }

    public function test_creating_a_user_does_not_attach_an_unselected_licensee(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $licensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
        ]);

        Livewire::actingAs($coach)
            ->test(UserManage::class)
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'alice@example.test')
            ->set('newUser.password', 'secret123')
            ->set('newUser.role', User::ROLE_PARENT)
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'alice@example.test')->firstOrFail();

        $this->assertDatabaseMissing('licensee_user', [
            'user_id' => $user->id,
            'licensee_id' => $licensee->id,
        ]);
    }

    public function test_unchecking_a_licensee_before_user_creation_does_not_attach_it(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $licensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
        ]);

        Livewire::actingAs($coach)
            ->test(UserManage::class)
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'alice@example.test')
            ->set('newUser.password', 'secret123')
            ->set('newUser.role', User::ROLE_PARENT)
            ->set('newUser.selectedLicensees', [$licensee->id])
            ->set('newUser.selectedLicensees', [])
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'alice@example.test')->firstOrFail();

        $this->assertDatabaseMissing('licensee_user', [
            'user_id' => $user->id,
            'licensee_id' => $licensee->id,
        ]);
    }

    public function test_editing_a_user_syncs_selected_licensees(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $user = User::factory()->create(['role' => User::ROLE_PARENT]);
        $previousLicensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
        ]);
        $selectedLicensee = Licensee::create([
            'firstname' => 'Bob',
            'name' => 'Martin',
        ]);
        $user->licensees()->attach($previousLicensee);

        $component = Livewire::actingAs($coach)->test(UserManage::class);
        $userIndex = collect($component->get('users'))
            ->search(fn (array $row): bool => $row['id'] === $user->id);

        $component
            ->assertSee('Alice Dupont')
            ->set("users.$userIndex.selectedLicensees", [$selectedLicensee->id])
            ->call('saveUser', $userIndex)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('licensee_user', [
            'user_id' => $user->id,
            'licensee_id' => $previousLicensee->id,
        ]);
        $this->assertDatabaseHas('licensee_user', [
            'user_id' => $user->id,
            'licensee_id' => $selectedLicensee->id,
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

    public function test_licensee_selections_from_an_invitation_are_not_reused_for_the_next_invitation(): void
    {
        $coach = User::factory()->create(['role' => User::ROLE_COACH]);
        $firstLicensee = Licensee::create([
            'firstname' => 'Alice',
            'name' => 'Dupont',
        ]);
        $secondLicensee = Licensee::create([
            'firstname' => 'Bob',
            'name' => 'Martin',
        ]);
        $form = Livewire::actingAs($coach)->test(UserManage::class);

        $form
            ->set('newUser.firstname', 'Alice')
            ->set('newUser.name', 'Dupont')
            ->set('newUser.email', 'alice@example.test')
            ->set('newUser.role', User::ROLE_PARENT)
            ->set('newUser.selectedLicensees', [$firstLicensee->id])
            ->call('invit')
            ->call('startCreate')
            ->set('newUser.firstname', 'Bob')
            ->set('newUser.name', 'Martin')
            ->set('newUser.email', 'bob@example.test')
            ->set('newUser.role', User::ROLE_PARENT)
            ->set('newUser.selectedLicensees', [$secondLicensee->id])
            ->call('invit')
            ->assertHasNoErrors();

        $secondUser = User::where('email', 'bob@example.test')->firstOrFail();

        $this->assertDatabaseHas('licensee_user', [
            'user_id' => $secondUser->id,
            'licensee_id' => $secondLicensee->id,
        ]);
        $this->assertDatabaseMissing('licensee_user', [
            'user_id' => $secondUser->id,
            'licensee_id' => $firstLicensee->id,
        ]);
    }
}
