<?php

namespace Tests\Unit\Services;

use App\Models\FcmToken;
use App\Models\User;
use App\Services\FcmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use Tests\TestCase;

class FcmNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Mockery\MockInterface&Messaging */
    protected $messaging;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messaging = Mockery::mock(Messaging::class);
        $this->app->instance('firebase.messaging', $this->messaging);
        $this->app->instance(Messaging::class, $this->messaging);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function service(): FcmNotificationService
    {
        return new FcmNotificationService();
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

    private function successReport(string $token): SendReport
    {
        return SendReport::success(MessageTarget::with('token', $token), []);
    }

    private function unknownTokenReport(string $token): SendReport
    {
        return SendReport::failure(
            MessageTarget::with('token', $token),
            NotFound::becauseTokenNotFound($token)
        );
    }

    public function test_send_to_user_with_no_tokens_returns_zero(): void
    {
        $user = $this->createUser();

        $this->messaging->shouldNotReceive('sendMulticast');

        $count = $this->service()->sendToUser($user->id, 'Hello', 'World');

        $this->assertSame(0, $count);
    }

    public function test_send_to_user_sends_to_active_tokens_only(): void
    {
        $user = $this->createUser();

        // Active token (last_used_at 1 day ago)
        FcmToken::create([
            'user_id' => $user->id,
            'token' => 'active_token_1',
            'device_type' => 'android',
            'last_used_at' => now()->subDay(),
        ]);

        // Stale token (last_used_at 1 year ago)
        FcmToken::create([
            'user_id' => $user->id,
            'token' => 'stale_token_1',
            'device_type' => 'android',
            'last_used_at' => now()->subYear(),
        ]);

        // Never used token (last_used_at null) → should still be sent to
        FcmToken::create([
            'user_id' => $user->id,
            'token' => 'fresh_token_1',
            'device_type' => 'ios',
            'last_used_at' => null,
        ]);

        $this->messaging
            ->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(function ($message, $tokens) {
                sort($tokens);
                $expected = ['active_token_1', 'fresh_token_1'];
                sort($expected);
                return $tokens === $expected;
            })
            ->andReturn(MulticastSendReport::withItems([
                $this->successReport('active_token_1'),
                $this->successReport('fresh_token_1'),
            ]));

        $count = $this->service()->sendToUser($user->id, 'Title', 'Body');

        $this->assertSame(2, $count);
    }

    public function test_send_to_user_updates_last_used_at_on_success(): void
    {
        $user = $this->createUser();

        $oldTimestamp = now()->subMonth()->startOfSecond();
        $token = FcmToken::create([
            'user_id' => $user->id,
            'token' => 'token_to_bump',
            'last_used_at' => $oldTimestamp,
        ]);

        $this->messaging
            ->shouldReceive('sendMulticast')
            ->once()
            ->andReturn(MulticastSendReport::withItems([
                $this->successReport('token_to_bump'),
            ]));

        $this->service()->sendToUser($user->id, 'Title', 'Body');

        $token->refresh();
        $this->assertTrue(
            $token->last_used_at->gt($oldTimestamp),
            'last_used_at should have been bumped to a value strictly after the original timestamp'
        );
    }

    public function test_send_to_user_removes_unknown_token(): void
    {
        $user = $this->createUser();

        FcmToken::create([
            'user_id' => $user->id,
            'token' => 'good_token',
            'last_used_at' => now(),
        ]);
        FcmToken::create([
            'user_id' => $user->id,
            'token' => 'unregistered_token',
            'last_used_at' => now(),
        ]);

        $this->messaging
            ->shouldReceive('sendMulticast')
            ->once()
            ->andReturn(MulticastSendReport::withItems([
                $this->successReport('good_token'),
                $this->unknownTokenReport('unregistered_token'),
            ]));

        $this->service()->sendToUser($user->id, 'Title', 'Body');

        $this->assertDatabaseHas('fcm_tokens', ['token' => 'good_token']);
        $this->assertDatabaseMissing('fcm_tokens', ['token' => 'unregistered_token']);
    }

    public function test_send_to_user_swallows_chunk_exception_and_continues(): void
    {
        $user = $this->createUser();

        // 600 tokens → 2 chunks (500 + 100)
        for ($i = 0; $i < 600; $i++) {
            FcmToken::create([
                'user_id' => $user->id,
                'token' => 'token_' . $i,
                'last_used_at' => now(),
            ]);
        }

        // The first chunk call throws, the second succeeds — service must not bail out
        $this->messaging
            ->shouldReceive('sendMulticast')
            ->twice()
            ->andReturnUsing(function () {
                static $call = 0;
                $call++;
                if ($call === 1) {
                    throw new \RuntimeException('simulated FCM outage');
                }
                return MulticastSendReport::withItems([$this->successReport('token_500')]);
            });

        $count = $this->service()->sendToUser($user->id, 'Title', 'Body');

        // First chunk threw → 0; second chunk delivered 1
        $this->assertSame(1, $count);
    }

    public function test_send_to_users_with_empty_user_ids_returns_zero(): void
    {
        $this->messaging->shouldNotReceive('sendMulticast');

        $count = $this->service()->sendToUsers([], 'Title', 'Body');

        $this->assertSame(0, $count);
    }
}
