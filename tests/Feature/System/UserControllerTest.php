<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);

        // God Admin - uses is_admin boolean
        $this->admin = User::factory()->create(['is_admin' => true]);

        // Regular user
        $this->regularUser = User::factory()->create(['is_admin' => false]);
    }

    public function test_admin_can_list_users(): void
    {
        // Create additional users for testing pagination
        User::factory()->count(5)->create(['is_admin' => false]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.users.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'is_admin',
                        'active',
                        'created_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        // Verify pagination works
        $users = $response->json('data');
        $this->assertGreaterThan(0, count($users));

        // Verify admin user is in the list
        $userIds = array_column($users, 'id');
        $this->assertContains($this->admin->id, $userIds);
    }

    public function test_admin_can_search_users_by_name(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => false,
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_admin' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.users.index', ['search' => 'John']));

        $response->assertOk();

        $users = $response->json('data');
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]['name']);
    }

    public function test_admin_can_search_users_by_email(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'is_admin' => false,
        ]);

        User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'is_admin' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.users.index', ['search' => 'jane.smith']));

        $response->assertOk();

        $users = $response->json('data');
        $this->assertCount(1, $users);
        $this->assertEquals('jane.smith@example.com', $users[0]['email']);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson(route('system.users.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_list_users(): void
    {
        $response = $this->getJson(route('system.users.index'));

        $response->assertUnauthorized();
    }

    public function test_admin_can_create_user_with_roles(): void
    {
        $role = \Spatie\Permission\Models\Role::where('name', 'Read Only')->first();

        $userData = [
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@example.com',
            'roles' => [$role->id],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.store'), $userData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'is_admin',
                    'active',
                    'created_at',
                ],
            ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@example.com',
        ]);

        // Verify role was assigned
        $user = User::where('email', 'alice.johnson@example.com')->first();
        $this->assertTrue($user->hasRole('Read Only'));
    }

    public function test_admin_can_create_user_with_direct_permissions(): void
    {
        $dbViewPermission = \Spatie\Permission\Models\Permission::where('name', 'databases.view')->first();
        $credCreatePermission = \Spatie\Permission\Models\Permission::where('name', 'credentials.create')->first();

        $userData = [
            'name' => 'Bob Williams',
            'email' => 'bob.williams@example.com',
            'permissions' => [$dbViewPermission->id, $credCreatePermission->id],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.store'), $userData);

        $response->assertCreated();

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'name' => 'Bob Williams',
            'email' => 'bob.williams@example.com',
        ]);

        // Verify direct permissions were assigned
        $user = User::where('email', 'bob.williams@example.com')->first();
        $this->assertTrue($user->hasDirectPermission('databases.view'));
        $this->assertTrue($user->hasDirectPermission('credentials.create'));
    }

    public function test_created_user_has_password_changed_at_null(): void
    {
        $userData = [
            'name' => 'Charlie Brown',
            'email' => 'charlie.brown@example.com',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.store'), $userData);

        $response->assertCreated();

        $user = User::where('email', 'charlie.brown@example.com')->first();

        // Verify password_changed_at is null
        $this->assertNull($user->password_changed_at);

        // Verify user needs password change
        $this->assertTrue($user->needsPasswordChange());
    }

    public function test_created_user_is_active_by_default(): void
    {
        $userData = [
            'name' => 'Diana Prince',
            'email' => 'diana.prince@example.com',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.store'), $userData);

        $response->assertCreated();

        $user = User::where('email', 'diana.prince@example.com')->first();

        // Verify user is active
        $this->assertTrue($user->active);
        $this->assertTrue($user->isActive());
    }

    public function test_created_user_has_default_password(): void
    {
        $userData = [
            'name' => 'Eve Adams',
            'email' => 'eve.adams@example.com',
        ];

        $this->actingAs($this->admin)
            ->postJson(route('system.users.store'), $userData);

        $user = User::where('email', 'eve.adams@example.com')->first();

        // Verify password is hashed (not plain text)
        $this->assertNotEquals('password123', $user->password);
        $this->assertNotEmpty($user->password);
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $userData = [
            'name' => 'Frank Castle',
            'email' => 'frank.castle@example.com',
        ];

        $response = $this->actingAs($this->regularUser)
            ->postJson(route('system.users.store'), $userData);

        $response->assertForbidden();

        // Verify user was not created
        $this->assertDatabaseMissing('users', [
            'email' => 'frank.castle@example.com',
        ]);
    }

    public function test_admin_can_view_user_profile(): void
    {
        // Create a user with roles and credentials
        $user = User::factory()->create(['is_admin' => false]);
        $user->assignRole('Read Only');

        $credential = Credential::factory()->create();
        $database = Database::factory()->create();

        $credential->databases()->attach($database);
        $user->credentials()->attach($credential);

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.users.show', $user));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'is_admin',
                    'active',
                    'password_changed_at',
                    'created_at',
                    'roles',
                    'direct_permissions',
                    'all_permissions',
                    'credentials',
                    'databases',
                ],
            ]);

        $data = $response->json('data');

        // Verify roles are included
        $this->assertIsArray($data['roles']);
        $this->assertCount(1, $data['roles']);

        // Verify credentials are included
        $this->assertIsArray($data['credentials']);
        $this->assertCount(1, $data['credentials']);
    }

    public function test_user_profile_includes_all_permissions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        // Assign role with permissions
        $user->assignRole('Full Access');

        // Assign direct permission
        $user->givePermissionTo('users.delete');

        $response = $this->actingAs($this->admin)
            ->getJson(route('system.users.show', $user));

        $response->assertOk();

        $data = $response->json('data');

        // Verify permissions include both role and direct permissions
        $this->assertIsArray($data['all_permissions']);
        $this->assertGreaterThan(0, count($data['all_permissions']));

        $permissionNames = array_column($data['all_permissions'], 'name');
        $this->assertContains('users.delete', $permissionNames);
    }

    public function test_non_admin_cannot_view_user_profile(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($this->regularUser)
            ->getJson(route('system.users.show', $user));

        $response->assertForbidden();
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'is_admin' => false,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->actingAs($this->admin)
            ->putJson(route('system.users.update', $user), $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'is_admin',
                    'active',
                ],
            ]);

        // Verify user was updated in database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        // Verify response contains updated data
        $data = $response->json('data');
        $this->assertEquals('Updated Name', $data['name']);
        $this->assertEquals('updated@example.com', $data['email']);
    }

    public function test_admin_can_deactivate_user_via_update(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'active' => true,
        ]);

        $updateData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'active' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->putJson(route('system.users.update', $user), $updateData);

        $response->assertOk();

        $user->refresh();
        $this->assertFalse($user->active);
        $this->assertFalse($user->isActive());
    }

    public function test_non_admin_cannot_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'is_admin' => false,
        ]);

        $updateData = [
            'name' => 'Hacked Name',
            'email' => 'hacked@example.com',
        ];

        $response = $this->actingAs($this->regularUser)
            ->putJson(route('system.users.update', $user), $updateData);

        $response->assertForbidden();

        // Verify user was not updated
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'name' => 'Hacked Name',
        ]);
    }

    public function test_admin_can_sync_user_roles_and_permissions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        // Initially assign Read Only role
        $user->assignRole('Read Only');

        $fullAccessRole = \Spatie\Permission\Models\Role::where('name', 'Full Access')->first();
        $deletePermission = \Spatie\Permission\Models\Permission::where('name', 'users.delete')->first();

        $syncData = [
            'roles' => [$fullAccessRole->id],
            'permissions' => [$deletePermission->id],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.permissions.sync', $user), $syncData);

        $response->assertOk();

        $user->refresh();

        // Verify roles were synced (Read Only removed, Full Access added)
        $this->assertTrue($user->hasRole('Full Access'));
        $this->assertFalse($user->hasRole('Read Only'));

        // Verify direct permissions were synced
        $this->assertTrue($user->hasDirectPermission('users.delete'));
    }

    public function test_sync_can_remove_all_roles(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $user->assignRole('Full Access');

        $syncData = [
            'roles' => [],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.permissions.sync', $user), $syncData);

        $response->assertOk();

        $user->refresh();

        // Verify all roles were removed
        $this->assertCount(0, $user->roles);
        $this->assertFalse($user->hasRole('Full Access'));
    }

    public function test_sync_can_remove_all_direct_permissions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $user->givePermissionTo(['databases.view', 'credentials.create']);

        $syncData = [
            'permissions' => [],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.permissions.sync', $user), $syncData);

        $response->assertOk();

        $user->refresh();

        // Verify all direct permissions were removed
        $this->assertCount(0, $user->getDirectPermissions());
    }

    public function test_sync_without_roles_removes_all_roles(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $user->assignRole('Full Access');

        $deletePermission = \Spatie\Permission\Models\Permission::where('name', 'users.delete')->first();

        // Sync only direct permissions, not roles (empty array)
        $syncData = [
            'permissions' => [$deletePermission->id],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('system.users.permissions.sync', $user), $syncData);

        $response->assertOk();

        $user->refresh();

        // Verify role was removed (because sync with empty array removes all)
        $this->assertCount(0, $user->roles);

        // Verify direct permission was added
        $this->assertTrue($user->hasDirectPermission('users.delete'));
    }

    public function test_non_admin_cannot_sync_permissions(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $fullAccessRole = \Spatie\Permission\Models\Role::where('name', 'Full Access')->first();

        $syncData = [
            'roles' => [$fullAccessRole->id],
        ];

        $response = $this->actingAs($this->regularUser)
            ->postJson(route('system.users.permissions.sync', $user), $syncData);

        $response->assertForbidden();

        $user->refresh();

        // Verify roles were not changed
        $this->assertCount(0, $user->roles);
    }

    public function test_admin_cannot_delete_last_admin(): void
    {
        // Ensure only one admin exists
        User::where('is_admin', true)->where('id', '!=', $this->admin->id)->delete();

        $this->assertEquals(1, User::where('is_admin', true)->count());

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.users.destroy', $this->admin));

        $response->assertStatus(302); // Redirect with error

        // Verify admin is still active (not deactivated)
        $this->admin->refresh();
        $this->assertTrue($this->admin->active);
    }

    public function test_admin_can_delete_another_admin(): void
    {
        // Create another admin
        $anotherAdmin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.users.destroy', $anotherAdmin));

        $response->assertOk()
            ->assertJson([
                'message' => 'User deactivated successfully',
            ]);

        // Verify admin was deactivated (soft delete)
        $anotherAdmin->refresh();
        $this->assertFalse($anotherAdmin->active);

        // Verify user still exists in database (not hard deleted)
        $this->assertDatabaseHas('users', [
            'id' => $anotherAdmin->id,
        ]);
    }

    public function test_admin_can_deactivate_user(): void
    {
        $user = User::factory()->create(['active' => true]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.users.destroy', $user));

        $response->assertOk()
            ->assertJson([
                'message' => 'User deactivated successfully',
            ]);

        // Verify user was deactivated
        $user->refresh();
        $this->assertFalse($user->active);
    }

    public function test_non_admin_cannot_deactivate_user(): void
    {
        $user = User::factory()->create(['active' => true]);

        $response = $this->actingAs($this->regularUser)
            ->deleteJson(route('system.users.destroy', $user));

        $response->assertForbidden();

        // Verify user is still active
        $user->refresh();
        $this->assertTrue($user->active);
    }

    public function test_deactivate_user_via_json_returns_success_message(): void
    {
        $user = User::factory()->create(['active' => true]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.users.destroy', $user));

        $response->assertOk()
            ->assertJson([
                'message' => 'User deactivated successfully',
            ]);

        $user->refresh();
        $this->assertFalse($user->active);
    }

    public function test_cannot_delete_last_admin_via_json(): void
    {
        // Ensure only one admin exists
        User::where('is_admin', true)->where('id', '!=', $this->admin->id)->delete();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('system.users.destroy', $this->admin));

        $response->assertStatus(302); // Redirect with error

        $this->admin->refresh();
        $this->assertTrue($this->admin->active);
    }
}
