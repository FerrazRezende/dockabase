<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\FeaturePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeaturePolicyTest extends TestCase
{
    use RefreshDatabase;

    private FeaturePolicy $policy;
    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FeaturePolicy();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Create regular user
        $this->user = User::factory()->create();

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_any_features(): void
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
    }

    public function test_admin_can_view_features(): void
    {
        $this->assertTrue($this->policy->view($this->admin));
    }

    public function test_admin_can_activate_features(): void
    {
        $this->assertTrue($this->policy->activate($this->admin));
    }

    public function test_admin_can_deactivate_features(): void
    {
        $this->assertTrue($this->policy->deactivate($this->admin));
    }

    public function test_non_admin_cannot_view_any_features(): void
    {
        $this->assertFalse($this->policy->viewAny($this->user));
    }

    public function test_non_admin_cannot_activate_features(): void
    {
        $this->assertFalse($this->policy->activate($this->user));
    }
}
