<?php

namespace Tests\Unit\Listeners;

use App\Events\NotificationSent;
use App\Listeners\SendFcmPushNotification;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendFcmPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_is_queueable(): void
    {
        $this->assertContains(
            ShouldQueue::class,
            class_implements(SendFcmPushNotification::class),
            'SendFcmPushNotification must implement ShouldQueue'
        );
    }

    public function test_handle_calls_send_to_user_with_expected_payload(): void
    {
        $user = $this->createUser();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'task',
            'title' => 'Task Assigned',
            'message' => 'John assigned you to task: Fix login bug',
            'data' => null,
            'is_read' => false,
            'action_url' => '/tasks/42',
            'icon' => '📋',
        ]);

        $expectedNotificationId = (string) $notification->id;

        $fcm = Mockery::mock(FcmNotificationService::class);
        $fcm->shouldReceive('sendToUser')
            ->once()
            ->withArgs(function ($userId, $title, $body, $data) use ($user, $expectedNotificationId) {
                return $userId === $user->id
                    && $title === 'Task Assigned'
                    && $body === 'John assigned you to task: Fix login bug'
                    && $data === [
                        'notification_id' => $expectedNotificationId,
                        'type'            => 'task',
                        'action_url'      => '/tasks/42',
                        'icon'            => '📋',
                    ];
            })
            ->andReturn(1);

        $listener = new SendFcmPushNotification($fcm);
        $listener->handle(new NotificationSent($notification));

        // The Mockery expectation above is the actual assertion; add an
        // explicit one to avoid PHPUnit "no assertions" warnings.
        $this->addToAssertionCount(1);
    }

    public function test_handle_coerces_null_optional_fields_to_empty_strings(): void
    {
        $user = $this->createUser();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'task',
            'title' => 'Hi',
            'message' => 'there',
            'data' => null,
            'is_read' => false,
            'action_url' => null,   // nullable column
            'icon' => null,         // nullable column
        ]);

        $fcm = Mockery::mock(FcmNotificationService::class);
        $fcm->shouldReceive('sendToUser')
            ->once()
            ->withArgs(function ($userId, $title, $body, $data) use ($user) {
                // All data values must be strings (FCM HTTP v1 requirement)
                foreach ($data as $k => $v) {
                    if (!is_string($v)) {
                        return false;
                    }
                }
                return $userId === $user->id
                    && $title === 'Hi'
                    && $body === 'there'
                    && $data['type'] === 'task'
                    && $data['action_url'] === ''
                    && $data['icon'] === '';
            })
            ->andReturn(0);

        $listener = new SendFcmPushNotification($fcm);
        $listener->handle(new NotificationSent($notification));

        $this->addToAssertionCount(1);
    }

    public function test_data_payload_contains_no_click_action(): void
    {
        $user = $this->createUser();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'task',
            'title' => 'Title',
            'message' => 'Body',
            'is_read' => false,
        ]);

        $fcm = Mockery::mock(FcmNotificationService::class);
        $fcm->shouldReceive('sendToUser')
            ->once()
            ->withArgs(function ($userId, $title, $body, $data) {
                return !array_key_exists('click_action', $data);
            })
            ->andReturn(0);

        $listener = new SendFcmPushNotification($fcm);
        $listener->handle(new NotificationSent($notification));

        $this->addToAssertionCount(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
