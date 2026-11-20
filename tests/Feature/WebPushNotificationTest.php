<?php

namespace Tests\Feature;

use App\Livewire\Game\Edit;
use App\Models\Game;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use App\Notifications\GameNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WebPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_worker_uses_notification_click_url(): void
    {
        $serviceWorker = file_get_contents(base_path('public/service-worker.js'));

        $this->assertStringContainsString("self.addEventListener('notificationclick'", $serviceWorker);
        $this->assertMatchesRegularExpression('/event\.notification(?:\?\.)?data(?:\?\.)?url/', $serviceWorker);
        $this->assertStringContainsString("clients.openWindow", $serviceWorker);
    }

    public function test_send_notification_only_for_members_with_maybe_availability(): void
    {
        Notification::fake();

        $team = Team::factory()->create();
        $game = Game::factory()->create(['team_id' => $team->id]);

        $userMaybe = User::factory()->create();
        $userYes = User::factory()->create();

        $memberMaybe = Member::factory()->create();
        $memberYes = Member::factory()->create();

        $userMaybe->licensees()->attach($memberMaybe->licensee);
        $userYes->licensees()->attach($memberYes->licensee);

        $game->members()->attach($memberMaybe->id, ['availability' => 'maybe']);
        $game->members()->attach($memberYes->id, ['availability' => 'yes']);

        $component = new Edit();
        $component->game = $game;

        $component->sendNotification();

        Notification::assertSentTo($userMaybe, GameNotification::class);
        Notification::assertNotSentTo($userYes, GameNotification::class);
    }

    public function test_send_notification_when_member_availability_is_null(): void
    {
        Notification::fake();

        $team = Team::factory()->create();
        $game = Game::factory()->create(['team_id' => $team->id]);

        $user = User::factory()->create();
        $member = Member::factory()->create();

        $user->licensees()->attach($member->licensee);
        $game->members()->attach($member->id, ['availability' => null]);

        $component = new Edit();
        $component->game = $game;

        $component->sendNotification();

        Notification::assertSentTo($user, GameNotification::class);
    }
}
