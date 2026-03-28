<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create God Admin user (system owner)
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@dockabase.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => true, // God Admin - full system access
        ]);

        // Create super-admin role
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        // Assign role to user
        $user->assignRole($role);
    }
}
