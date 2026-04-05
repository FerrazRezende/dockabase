<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_needs_password_change_when_null(): void
    {
        // Given: a user with password_changed_at set to null
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        // When: checking if the user needs password change
        $needsChange = $user->password_changed_at === null;

        // Then: the user should be flagged as needing password change
        $this->assertTrue($needsChange);
        $this->assertNull($user->password_changed_at);
    }

    public function test_user_does_not_need_password_change_when_set(): void
    {
        // Given: a user with password_changed_at set to a valid timestamp
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        // When: checking if the user needs password change
        $needsChange = $user->password_changed_at === null;

        // Then: the user should NOT be flagged as needing password change
        $this->assertFalse($needsChange);
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_user_can_be_deactivated(): void
    {
        // Given: an active user
        $user = User::factory()->create([
            'active' => true,
        ]);

        // Assert initial state
        $this->assertTrue($user->active);

        // When: deactivating the user
        $user->update(['active' => false]);
        $user->refresh();

        // Then: the user should be inactive
        $this->assertFalse($user->active);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'active' => false,
        ]);
    }

    public function test_user_can_be_activated(): void
    {
        // Given: an inactive user
        $user = User::factory()->create([
            'active' => false,
        ]);

        // Assert initial state
        $this->assertFalse($user->active);

        // When: activating the user
        $user->update(['active' => true]);
        $user->refresh();

        // Then: the user should be active
        $this->assertTrue($user->active);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'active' => true,
        ]);
    }

    public function test_inactive_user_is_not_active(): void
    {
        // Given: an inactive user
        $user = User::factory()->create([
            'active' => false,
        ]);

        // When & Then: the user should not be active
        $this->assertFalse($user->active);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'active' => false,
        ]);
    }
}
