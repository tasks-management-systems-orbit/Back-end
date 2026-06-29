<?php

namespace Tests\Feature\Notifications;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationSentTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_sent_is_dispatched_when_service_sends(): void
    {
        // After the wiring fix, the class lives at App\Events.
        Event::fake([NotificationSent::class]);

        $user = $this->createUser();
        $service = app(NotificationService::class);
        $notification = $service->send(
            $user->id,
            'My Title',
            'My Message',
            'info',
            null,
            '/x',
            '📋'
        );

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title'   => 'My Title',
            'message' => 'My Message',
        ]);

        Event::assertDispatched(NotificationSent::class);
    }

    public function test_notification_sent_is_no_longer_broadcastable(): void
    {
        // After the FCM migration, NotificationSent must NOT implement
        // ShouldBroadcast — otherwise Laravel would try to push it through
        // the (now removed) Pusher connection.
        $this->assertNotContains(
            ShouldBroadcast::class,
            class_implements(NotificationSent::class),
            'NotificationSent must not implement ShouldBroadcast after the FCM migration'
        );
    }

    public function test_send_to_many_dispatches_one_event_per_user(): void
    {
        Event::fake([NotificationSent::class]);

        $userIds = [];
        for ($i = 0; $i < 3; $i++) {
            $userIds[] = $this->createUser()->id;
        }

        $service = app(NotificationService::class);
        $service->sendToMany(
            $userIds,
            'Bulk Title',
            'Bulk Message'
        );

        Event::assertDispatchedTimes(NotificationSent::class, 3);
    }

    private function createUser(array $attrs = []): User
    {
        static $i = 0;
        $i++;
        $user = User::create(array_merge([
            'name' => 'Test User ' . $i,
            'username' => 'test_user_' . uniqid() . '_' . $i,
            'email' => 'test_' . uniqid() . '_' . $i . '@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ], $attrs));
        $user->is_active = true;
        $user->save();
        return $user;
    }
}
