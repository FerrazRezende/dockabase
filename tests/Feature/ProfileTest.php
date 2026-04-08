<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
            'password_changed_at' => now()->subDays(30),
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->post('/profile/password', [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertTrue(\Hash::check('new-password-123', $user->password));
        $this->assertNotNull($user->password_changed_at);
        $this->assertGreaterThan(
            $user->password_changed_at->subSeconds(5),
            now()
        );
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->post('/profile/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/profile');

        $this->assertTrue(\Hash::check('password', $user->fresh()->password));
    }

    public function test_password_must_be_minimum_8_characters(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->post('/profile/password', [
                'current_password' => 'password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');
    }

    public function test_password_must_be_confirmed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->post('/profile/password', [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');
    }

    public function test_password_changed_at_is_set_after_update(): void
    {
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        $this->assertNull($user->password_changed_at);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->post('/profile/password', [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNotNull($user->password_changed_at);
        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->password_changed_at->timestamp,
            5
        );
    }

    public function test_admin_can_update_password_even_with_password_changed_at_null(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'password_changed_at' => null,
        ]);

        $this->assertNull($admin->password_changed_at);

        $response = $this
            ->actingAs($admin)
            ->from('/profile')
            ->post('/profile/password', [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $admin->refresh();

        $this->assertNotNull($admin->password_changed_at);
        $this->assertTrue(\Hash::check('new-password-123', $admin->password));
    }
}
