<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ImpersonateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    private User $anotherAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin User']);
        $this->regularUser = User::factory()->create(['is_admin' => false, 'name' => 'Regular User']);
        $this->anotherAdmin = User::factory()->create(['is_admin' => true, 'name' => 'Another Admin']);
    }

    public function test_admin_can_start_impersonating_regular_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('system.users.impersonate.start', $this->regularUser));

        $response->assertRedirect(route('dashboard'));

        // Verify session contains original and target IDs
        $this->assertEquals($this->admin->id, Session::get('original_user_id'));
        $this->assertEquals($this->regularUser->id, Session::get('impersonating_id'));

        // Verify the current authenticated user is now the target user
        $this->assertEquals($this->regularUser->id, Auth::id());
    }

    public function test_admin_cannot_impersonate_another_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('system.users.impersonate.start', $this->anotherAdmin));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        // Verify session was not modified
        $this->assertNull(Session::get('original_user_id'));
        $this->assertNull(Session::get('impersonating_id'));

        // Verify still authenticated as original admin
        $this->assertEquals($this->admin->id, Auth::id());
    }

    public function test_non_admin_cannot_impersonate(): void
    {
        $nonAdmin = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($nonAdmin)
            ->post(route('system.users.impersonate.start', $this->regularUser));

        $response->assertForbidden();

        // Verify session was not modified
        $this->assertNull(Session::get('original_user_id'));
        $this->assertNull(Session::get('impersonating_id'));
    }

    public function test_can_stop_impersonating(): void
    {
        // Start impersonating
        Session::put('original_user_id', $this->admin->id);
        Session::put('impersonating_id', $this->regularUser->id);

        // Act as the regular user (simulating impersonation)
        Auth::login($this->regularUser);

        $response = $this->actingAs($this->regularUser)
            ->post(route('system.impersonate.stop'));

        $response->assertRedirect(route('system.users.index'));

        // Verify session keys are cleared
        $this->assertNull(Session::get('original_user_id'));
        $this->assertNull(Session::get('impersonating_id'));

        // Verify authenticated user is now the original admin
        $this->assertEquals($this->admin->id, Auth::id());
    }

    public function test_session_contains_original_and_target_ids(): void
    {
        // Start impersonating
        $response = $this->actingAs($this->admin)
            ->post(route('system.users.impersonate.start', $this->regularUser));

        $response->assertRedirect();

        // Verify session structure
        $this->assertArrayHasKey('original_user_id', Session::all());
        $this->assertArrayHasKey('impersonating_id', Session::all());

        // Verify correct values
        $this->assertEquals($this->admin->id, Session::get('original_user_id'));
        $this->assertEquals($this->regularUser->id, Session::get('impersonating_id'));
    }

    public function test_stop_impersonating_without_session_redirects_to_dashboard(): void
    {
        // No impersonation session set

        $response = $this->actingAs($this->regularUser)
            ->post(route('system.impersonate.stop'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_unauthenticated_cannot_start_impersonating(): void
    {
        $response = $this->post(route('system.users.impersonate.start', $this->regularUser));

        $response->assertRedirect();
    }

    public function test_impersonate_target_user_must_exist(): void
    {
        $nonExistentUserId = 999999;

        $response = $this->actingAs($this->admin)
            ->post(route('system.users.impersonate.start', $nonExistentUserId));

        $response->assertNotFound();
    }

    public function test_admin_cannot_impersonate_self(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('system.users.impersonate.start', $this->admin));

        // Should fail because admin is trying to impersonate another admin (self)
        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        // Verify session was not modified
        $this->assertNull(Session::get('original_user_id'));
        $this->assertNull(Session::get('impersonating_id'));
    }
}
