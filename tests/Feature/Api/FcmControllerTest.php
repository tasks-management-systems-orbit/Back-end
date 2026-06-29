<?php

namespace Tests\Feature\Api;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmControllerTest extends TestCase
{
    use RefreshDatabase;

    private function authedUser(): User
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);
        return $user;
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

    // ---- register ----

    public function test_register_creates_new_token(): void
    {
        $user = $this->authedUser();

        $response = $this->postJson('/api/fcm/register', [
            'token' => 'fcm_token_abc_123',
            'device_type' => 'android',
            'device_name' => 'Pixel 7',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm_token_abc_123',
            'device_type' => 'android',
            'device_name' => 'Pixel 7',
        ]);
    }

    public function test_register_updates_last_used_at_on_duplicate_token(): void
    {
        $user = $this->authedUser();

        FcmToken::create([
            'user_id' => $user->id,
            'token' => 'duplicate_token',
            'last_used_at' => now()->subDays(10),
        ]);

        $response = $this->postJson('/api/fcm/register', [
            'token' => 'duplicate_token',
            'device_type' => 'ios',
        ]);

        $response->assertOk();

        $row = FcmToken::where('user_id', $user->id)
            ->where('token', 'duplicate_token')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('ios', $row->device_type);
        $this->assertTrue($row->last_used_at->gt(now()->subMinute()));
    }

    public function test_register_rejects_invalid_device_type(): void
    {
        $this->authedUser();

        $response = $this->postJson('/api/fcm/register', [
            'token' => 'some_token',
            'device_type' => 'windows-phone',  // not in the allow-list
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_type']);
    }

    public function test_register_requires_authentication(): void
    {
        $response = $this->postJson('/api/fcm/register', [
            'token' => 'some_token',
        ]);

        $response->assertStatus(401);
    }

    public function test_register_requires_token_field(): void
    {
        $this->authedUser();

        $response = $this->postJson('/api/fcm/register', [
            'device_type' => 'android',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    // ---- unregister ----

    public function test_unregister_removes_only_callers_token(): void
    {
        $caller = $this->authedUser();
        $other = $this->createUser();

        FcmToken::create(['user_id' => $caller->id, 'token' => 'mine']);
        FcmToken::create(['user_id' => $other->id,  'token' => 'theirs']);

        $response = $this->postJson('/api/fcm/unregister', [
            'token' => 'mine',
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('fcm_tokens', ['token' => 'mine', 'user_id' => $caller->id]);
        $this->assertDatabaseHas('fcm_tokens', ['token' => 'theirs', 'user_id' => $other->id]);
    }

    public function test_unregister_with_missing_token_is_idempotent(): void
    {
        $this->authedUser();

        $response = $this->postJson('/api/fcm/unregister', [
            'token' => 'never_registered',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // ---- index ----

    public function test_index_returns_only_callers_tokens_ordered_by_last_used_at_desc(): void
    {
        $caller = $this->authedUser();
        $other = $this->createUser();

        FcmToken::create([
            'user_id' => $caller->id,
            'token' => 'a',
            'device_name' => 'A',
            'last_used_at' => now()->subDays(3),
        ]);
        FcmToken::create([
            'user_id' => $caller->id,
            'token' => 'b',
            'device_name' => 'B',
            'last_used_at' => now()->subDays(1),  // more recent
        ]);
        FcmToken::create([
            'user_id' => $caller->id,
            'token' => 'c',
            'device_name' => 'C',
            'last_used_at' => now()->subDays(7),
        ]);
        FcmToken::create([
            'user_id' => $other->id,
            'token' => 'belongs_to_other',
            'device_name' => 'OTHER',
        ]);

        $response = $this->getJson('/api/fcm/tokens');

        $response->assertOk();

        $tokens = $response->json('data.tokens');
        $this->assertCount(3, $tokens);
        // The endpoint intentionally does NOT return `token` (the user
        // already knows their own tokens). Order is by last_used_at desc.
        $this->assertSame('B', $tokens[0]['device_name']);
        $this->assertSame('A', $tokens[1]['device_name']);
        $this->assertSame('C', $tokens[2]['device_name']);
        foreach ($tokens as $t) {
            $this->assertArrayNotHasKey('token', $t);
            $this->assertArrayHasKey('id', $t);
            $this->assertArrayHasKey('device_name', $t);
            $this->assertArrayHasKey('last_used_at', $t);
        }
    }

    public function test_index_returns_empty_list_when_no_devices(): void
    {
        $this->authedUser();

        $response = $this->getJson('/api/fcm/tokens');

        $response->assertOk()
            ->assertJson(['data' => ['tokens' => []]]);
    }
}
