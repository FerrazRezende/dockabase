<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create God Admin user (system owner)
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@dockabase.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        // Create super-admin role
        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        // Assign role to admin
        $admin->assignRole($role);

        // Create 10 test users
        $users = [
            ['name' => 'Alice Santos', 'email' => 'alice@dockabase.com'],
            ['name' => 'Bob Ferreira', 'email' => 'bob@dockabase.com'],
            ['name' => 'Carlos Lima', 'email' => 'carlos@dockabase.com'],
            ['name' => 'Diana Costa', 'email' => 'diana@dockabase.com'],
            ['name' => 'Eduardo Silva', 'email' => 'eduardo@dockabase.com'],
            ['name' => 'Fernanda Rocha', 'email' => 'fernanda@dockabase.com'],
            ['name' => 'Gabriel Mendes', 'email' => 'gabriel@dockabase.com'],
            ['name' => 'Helena Alves', 'email' => 'helena@dockabase.com'],
            ['name' => 'Igor Nascimento', 'email' => 'igor@dockabase.com'],
            ['name' => 'Julia Oliveira', 'email' => 'julia@dockabase.com'],
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
            ]);
        }
    }
}
