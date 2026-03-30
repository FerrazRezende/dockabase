<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Database;
use App\Models\User;
use App\Policies\DatabasePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabasePolicyTest extends TestCase
{
    use RefreshDatabase;

    private DatabasePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DatabasePolicy;
    }

    public function test_admin_can_view_any(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_non_admin_cannot_view_any(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_admin_can_create(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->create($admin));
    }

    public function test_admin_can_update(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $database = Database::factory()->create();

        $this->assertTrue($this->policy->update($admin, $database));
    }

    public function test_admin_can_delete(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $database = Database::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $database));
    }
}
