<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador si no existe
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'superadmin' => true,
                'role_id' => null,
            ]
        );

        // Crear usuario normal si no existe
        User::firstOrCreate(
            ['email' => 'usuario@test.com'],
            [
                'email' => 'usuario@test.com',
                'password' => Hash::make('password123'),
                'superadmin' => false,
                'role_id' => null,
            ]
        );
    }
}
