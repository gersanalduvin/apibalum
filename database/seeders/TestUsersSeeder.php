<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario superadmin
        User::firstOrCreate(
            ['email' => 'superadmin@test.com'],
            [
                'email' => 'superadmin@test.com',
                'password' => Hash::make('password'),
                'superadmin' => true,
                'role_id' => null,
            ]
        );

        // Buscar el rol de Administrador (si existe)
        $adminRole = Role::where('nombre', 'Administrador')->first();
        
        if ($adminRole) {
            // Crear usuario con rol de administrador
            User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'email' => 'admin@test.com',
                    'password' => Hash::make('password'),
                    'superadmin' => false,
                    'role_id' => $adminRole->id,
                ]
            );
        }

        // Crear usuario sin rol
        User::firstOrCreate(
            ['email' => 'user@test.com'],
            [
                'email' => 'user@test.com',
                'password' => Hash::make('password'),
                'superadmin' => false,
                'role_id' => null,
            ]
        );
    }
}
