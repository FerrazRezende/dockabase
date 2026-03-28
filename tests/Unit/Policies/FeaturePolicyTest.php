<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\FeaturePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturePolicyTest extends TestCase
{
    use RefreshDatabase;

    private FeaturePolicy $policy;
    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new FeaturePolicy();

        // God Admin
        $this->admin = User::factory()->create(['is_admin' => true]);

        // Regular user
        $this->user = User::factory()->create(['is_admin' => false]);
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
