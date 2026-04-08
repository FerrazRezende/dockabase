<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_force_password_change_page(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/force-password-change');

        $response->assertStatus(200);
    }

    public function test_user_can_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
            'password_changed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/force-password-change')
            ->post('/force-password-change', [
                'current_password' => 'old-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertTrue(Hash::check('new-secure-password', $user->refresh()->password));
        $this->assertNotNull($user->password_changed_at);
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/force-password-change')
            ->post('/force-password-change', [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/force-password-change');

        // Password should remain unchanged
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        $this->assertNull($user->password_changed_at);
    }

    public function test_password_must_be_minimum_8_characters(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/force-password-change')
            ->post('/force-password-change', [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/force-password-change');
    }

    public function test_password_must_be_confirmed(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/force-password-change')
            ->post('/force-password-change', [
                'current_password' => 'password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/force-password-change');
    }

    public function test_all_fields_are_required(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/force-password-change')
            ->post('/force-password-change', [
                'current_password' => '',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response
            ->assertSessionHasErrors(['password'])
            ->assertRedirect('/force-password-change');
    }

    public function test_current_password_is_required(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/force-password-change')
            ->post('/force-password-change', [
                'current_password' => '',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/force-password-change');
    }

    public function test_password_changed_at_is_set_after_successful_update(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $this->assertNull($user->password_changed_at);
        $this->assertTrue($user->needsPasswordChange());

        $this
            ->actingAs($user)
            ->post('/force-password-change', [
                'current_password' => 'password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ]);

        $user->refresh();
        $this->assertNotNull($user->password_changed_at);
        $this->assertFalse($user->needsPasswordChange());
    }
}
