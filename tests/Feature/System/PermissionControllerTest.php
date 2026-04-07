<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);

        // God Admin - uses is_admin boolean
        $this->admin = User::factory()->create(['is_admin' => true]);

        // Regular user
        $this->user = User::factory()->create(['is_admin' => false]);
    }

    public function test_index_returns_permissions_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('system.permissions.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'guard_name',
                    ],
                ],
                'links',
                'meta',
            ]);

        // Verify permissions from seeder exist
        $permissions = $response->json('data');
        $this->assertGreaterThan(0, count($permissions));

        // Verify some expected permissions
        $permissionNames = array_column($permissions, 'name');
        $this->assertContains('databases.view', $permissionNames);
        $this->assertContains('credentials.create', $permissionNames);
        $this->assertContains('users.update', $permissionNames);
    }

    public function test_index_returns_all_permissions_from_seeder(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('system.permissions.index'));

        $response->assertOk();

        // Verify all expected permissions from seeder exist
        $permissions = $response->json('data');
        $permissionNames = array_column($permissions, 'name');

        // Verify all permission groups from seeder
        $expectedPermissions = [
            'databases.view', 'databases.create', 'databases.update', 'databases.delete',
            'schemas.view', 'schemas.create', 'schemas.update', 'schemas.delete',
            'credentials.view', 'credentials.create', 'credentials.update', 'credentials.delete',
            'tables.view', 'tables.create', 'tables.update', 'tables.delete',
            'users.view', 'users.create', 'users.update', 'users.delete',
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertContains($permission, $permissionNames, "Permission {$permission} not found");
        }
    }

    public function test_index_forbidden_for_non_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('system.permissions.index'));

        $response->assertForbidden();
    }

    public function test_index_unauthenticated_returns_401(): void
    {
        $response = $this->getJson(route('system.permissions.index'));

        $response->assertUnauthorized();
    }

    public function test_permissions_are_read_only(): void
    {
        // POST /permissions should return Method Not Allowed (405)
        $response = $this->actingAs($this->admin)
            ->postJson(route('system.permissions.index'), [
                'name' => 'new.permission',
            ]);

        $response->assertStatus(405);
    }

    public function test_permissions_support_pagination(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('system.permissions.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }
}
