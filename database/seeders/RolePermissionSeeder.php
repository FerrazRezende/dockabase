<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions for the application
        $permissions = [
            // Databases
            'databases.view',
            'databases.create',
            'databases.update',
            'databases.delete',
            // Schemas
            'schemas.view',
            'schemas.create',
            'schemas.update',
            'schemas.delete',
            // Credentials
            'credentials.view',
            'credentials.create',
            'credentials.update',
            'credentials.delete',
            // Tables
            'tables.view',
            'tables.create',
            'tables.update',
            'tables.delete',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Create default "Full Access" role with all permissions
        $fullAccessRole = Role::firstOrCreate(
            ['name' => 'Full Access'],
            ['guard_name' => 'web']
        );

        // Assign all permissions to the role
        $fullAccessRole->syncPermissions(Permission::all());

        $this->command->info('Created '.count($permissions).' permissions.');
        $this->command->info('Created "Full Access" role with all permissions.');
    }
}
