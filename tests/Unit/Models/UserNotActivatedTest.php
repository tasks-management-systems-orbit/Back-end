<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotActivatedTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        static $i = 0;
        $i++;

        return User::create(array_merge([
            'name' => 'Test User '.$i,
            'username' => 'not_activated_user_'.uniqid().'_'.$i,
            'email' => 'not_activated_'.uniqid().'_'.$i.'@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ], $attrs));
    }

    public function test_fresh_active_and_verified_user_is_activated(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($user->isActivated());
    }

    public function test_unverified_user_is_not_activated(): void
    {
        $user = $this->makeUser(['email_verified_at' => null]);

        $this->assertFalse($user->isActivated());
    }

    public function test_inactive_user_is_not_activated(): void
    {
        $user = $this->makeUser(['is_active' => false]);

        $this->assertFalse($user->isActivated());
    }

    public function test_verified_but_inactive_user_is_still_activated(): void
    {
        // Only ONE of the two conditions fails (inactive), so the user is
        // still considered activated. The "not activated" definition
        // requires BOTH conditions to hold.
        $user = $this->makeUser(['is_active' => false]);

        // Sanity: hasVerifiedEmail() is true.
        $this->assertTrue($user->hasVerifiedEmail());
        // But is_active is false, so isActivated() must be false.
        $this->assertFalse($user->isActivated());
    }

    public function test_active_but_unverified_user_is_not_activated(): void
    {
        $user = $this->makeUser(['email_verified_at' => null, 'is_active' => true]);

        $this->assertFalse($user->isActivated());
    }

    public function test_scope_not_activated_returns_only_users_with_both_conditions(): void
    {
        $normal = $this->makeUser(); // active + verified
        $unverifiedOnly = $this->makeUser(['email_verified_at' => null, 'is_active' => true]);
        $inactiveOnly = $this->makeUser(['is_active' => false]);
        $both = $this->makeUser(['email_verified_at' => null, 'is_active' => false]);

        $ids = User::notActivated()->pluck('id')->all();

        // Only the user that is BOTH inactive AND unverified should be returned.
        $this->assertEqualsCanonicalizing([$both->id], $ids);
        $this->assertNotContains($normal->id, $ids);
        $this->assertNotContains($unverifiedOnly->id, $ids);
        $this->assertNotContains($inactiveOnly->id, $ids);
    }

    public function test_scope_not_activated_excludes_only_one_condition_users(): void
    {
        // Add a "only one condition" user and confirm it is excluded from
        // the scope's result set even though it is not "fully" not-activated.
        $this->makeUser(['email_verified_at' => null, 'is_active' => true]);
        $this->makeUser(['is_active' => false]);

        $count = User::notActivated()->count();

        $this->assertSame(0, $count);
    }
}
