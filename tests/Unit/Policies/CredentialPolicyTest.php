<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Credential;
use App\Models\User;
use App\Policies\CredentialPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CredentialPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CredentialPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CredentialPolicy;

        // Seed permissions required by checkPermission()
        $permissions = ['credentials.view', 'credentials.create', 'credentials.update', 'credentials.delete'];
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_admin_can_view_any(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_non_admin_can_view_any(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $user->givePermissionTo('credentials.view');

        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_admin_can_create(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($this->policy->create($admin));
    }

    public function test_admin_can_update(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $credential = Credential::factory()->create();

        $this->assertTrue($this->policy->update($admin, $credential));
    }

    public function test_admin_can_delete(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $credential = Credential::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $credential));
    }
}
