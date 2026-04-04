<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsurePasswordChangedTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_null_password_changed_at_is_redirected(): void
    {
        // Create user with null password_changed_at
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        // Attempt to access dashboard
        $response = $this->actingAs($user)
            ->get('/dashboard');

        // Should be redirected to force password change
        $response->assertRedirect(route('password.force-change'));
    }

    public function test_authenticated_user_with_password_changed_set_is_not_redirected(): void
    {
        // Create user with password_changed_at set
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        // Attempt to access dashboard
        $response = $this->actingAs($user)
            ->get('/dashboard');

        // Should access dashboard normally
        $response->assertOk();
    }

    public function test_admin_with_null_password_changed_at_is_not_redirected(): void
    {
        // Create admin user with null password_changed_at
        $admin = User::factory()->create([
            'is_admin' => true,
            'password_changed_at' => null,
        ]);

        // Attempt to access dashboard
        $response = $this->actingAs($admin)
            ->get('/dashboard');

        // Should access dashboard normally (admins are not required to change password)
        $response->assertOk();
    }

    public function test_unauthenticated_user_is_not_affected(): void
    {
        // Attempt to access login page without authentication
        $response = $this->get('/login');

        // Should access login page normally (no redirect loop)
        $response->assertOk();
    }

    public function test_force_password_change_route_is_excluded_from_middleware(): void
    {
        // Create user with null password_changed_at
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        // Access the force password change page
        $response = $this->actingAs($user)
            ->get(route('password.force-change'));

        // Should not redirect (avoid infinite loop)
        $response->assertOk();
    }

    public function test_force_password_change_update_route_is_excluded_from_middleware(): void
    {
        // Create user with null password_changed_at
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        // POST to the force password change update endpoint
        $response = $this->actingAs($user)
            ->post(route('password.force-change.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        // Should not redirect (avoid infinite loop)
        $response->assertRedirect(route('dashboard'));
    }

    public function test_api_requests_are_allowed_through(): void
    {
        // Create user with null password_changed_at
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        // Make API request (expects JSON)
        $response = $this->actingAs($user)
            ->getJson('/api/v1/features');

        // Should not redirect (API requests pass through)
        $response->assertOk();
    }

    public function test_middleware_allows_profile_edit_after_password_change(): void
    {
        // Create user with password_changed_at set
        $user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        // Access profile edit page
        $response = $this->actingAs($user)
            ->get('/profile');

        // Should access profile normally
        $response->assertOk();
    }

    public function test_middleware_redirects_from_profile_when_password_not_changed(): void
    {
        // Create user with null password_changed_at
        $user = User::factory()->create([
            'password_changed_at' => null,
        ]);

        // Attempt to access profile edit page
        $response = $this->actingAs($user)
            ->get('/profile');

        // Should be redirected to force password change
        $response->assertRedirect(route('password.force-change'));
    }
}
