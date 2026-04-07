<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_needs_password_change_when_password_changed_at_is_null(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $this->assertTrue($user->needsPasswordChange());
    }

    public function test_does_not_need_password_change_when_password_changed_at_is_set(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        $this->assertFalse($user->needsPasswordChange());
    }

    public function test_is_active_when_active_is_true(): void
    {
        $user = User::factory()->create([
            'active' => true,
        ]);

        $this->assertTrue($user->isActive());
    }

    public function test_is_not_active_when_active_is_false(): void
    {
        $user = User::factory()->create([
            'active' => false,
        ]);

        $this->assertFalse($user->isActive());
    }

    public function test_deactivate_sets_active_to_false(): void
    {
        $user = User::factory()->create([
            'active' => true,
        ]);

        $user->deactivate();

        $this->assertFalse($user->isActive());
        $this->assertFalse($user->active);
    }

    public function test_activate_sets_active_to_true(): void
    {
        $user = User::factory()->create([
            'active' => false,
        ]);

        $user->activate();

        $this->assertTrue($user->isActive());
        $this->assertTrue($user->active);
    }

    public function test_password_changed_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => '2024-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->password_changed_at);
        $this->assertEquals('2024-01-15 10:30:00', $user->password_changed_at->format('Y-m-d H:i:s'));
    }

    public function test_active_is_cast_to_boolean(): void
    {
        $user = User::factory()->create([
            'active' => 1,
        ]);

        $this->assertIsBool($user->active);
        $this->assertTrue($user->active);
    }

    public function test_deactivate_persists_to_database(): void
    {
        $user = User::factory()->create([
            'active' => true,
        ]);

        $user->deactivate();
        $user->save();

        $userFromDb = User::find($user->id);
        $this->assertFalse($userFromDb->active);
    }

    public function test_activate_persists_to_database(): void
    {
        $user = User::factory()->create([
            'active' => false,
        ]);

        $user->activate();
        $user->save();

        $userFromDb = User::find($user->id);
        $this->assertTrue($userFromDb->active);
    }
}
