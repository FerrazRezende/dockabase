<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_impersonating_user_can_access_page_as_target_user(): void
    {
        // Create admin user (impersonator)
        $admin = User::factory()->create(['name' => 'Admin User']);

        // Create target user to be impersonated
        $targetUser = User::factory()->create(['name' => 'Target User']);

        // Set up impersonation session
        $this->actingAs($admin)
            ->withSession(['impersonate_target_id' => $targetUser->id]);

        // Access a protected route
        $response = $this->get('/dashboard');

        // Response should be successful
        $response->assertStatus(200);
    }

    public function test_original_user_id_is_stored_in_session(): void
    {
        // Create admin user (impersonator)
        $admin = User::factory()->create(['name' => 'Admin User']);

        // Create target user to be impersonated
        $targetUser = User::factory()->create(['name' => 'Target User']);

        // Set up impersonation session
        $this->actingAs($admin)
            ->withSession(['impersonate_target_id' => $targetUser->id]);

        // Access a route
        $this->get('/dashboard');

        // Original user ID should be stored in session
        $this->assertEquals($admin->id, session('impersonate_original_id'));
    }

    public function test_request_uses_impersonated_user_for_authorization(): void
    {
        // Create admin user (impersonator)
        $admin = User::factory()->create(['name' => 'Admin User']);

        // Create target user to be impersonated
        $targetUser = User::factory()->create(['name' => 'Target User']);

        // Set up impersonation session
        $this->actingAs($admin)
            ->withSession(['impersonate_target_id' => $targetUser->id]);

        // Access a route and check the authenticated user
        $this->get('/dashboard');

        // The current authenticated user should be the target user
        $this->assertEquals($targetUser->id, auth()->id());
        $this->assertEquals('Target User', auth()->user()->name);
    }

    public function test_session_restores_after_request(): void
    {
        // Create admin user (impersonator)
        $admin = User::factory()->create(['name' => 'Admin User']);

        // Create target user to be impersonated
        $targetUser = User::factory()->create(['name' => 'Target User']);

        // Set up impersonation session with original user ID
        $this->actingAs($admin)
            ->withSession([
                'impersonate_target_id' => $targetUser->id,
                'impersonate_original_id' => $admin->id,
            ]);

        // Access a route
        $this->get('/dashboard');

        // During the request, auth should be the target user
        $this->assertEquals($targetUser->id, auth()->id());

        // The original user ID should remain in session for restoration
        $this->assertEquals($admin->id, session('impersonate_original_id'));
    }

    public function test_unauthenticated_user_bypasses_middleware(): void
    {
        // Create a user but don't authenticate
        $user = User::factory()->create();

        // Attempt to access login page without authentication
        $response = $this->get('/login');

        // Should access login page normally
        $response->assertOk();
    }

    public function test_normal_request_without_impersonation_passes_through(): void
    {
        // Create a regular user
        $user = User::factory()->create(['name' => 'Regular User']);

        // Act as user without impersonation session
        $response = $this->actingAs($user)
            ->get('/dashboard');

        // Should access dashboard normally
        $response->assertOk();

        // Auth should still be the original user
        $this->assertEquals($user->id, auth()->id());
        $this->assertEquals('Regular User', auth()->user()->name);
    }

    public function test_impersonation_target_user_must_exist(): void
    {
        // Create admin user (impersonator)
        $admin = User::factory()->create(['name' => 'Admin User']);

        // Set up impersonation session with non-existent user ID
        $this->actingAs($admin)
            ->withSession(['impersonate_target_id' => 99999]);

        // Access a route
        $response = $this->get('/dashboard');

        // Should still work (fallback to original user)
        $response->assertOk();

        // Auth should remain the original user if target doesn't exist
        $this->assertEquals($admin->id, auth()->id());
    }
}
