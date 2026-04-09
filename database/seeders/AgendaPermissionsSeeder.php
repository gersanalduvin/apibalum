<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class AgendaPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permisos del Módulo Agenda (Claves exactas de PermissionService)
        $newPermissions = [
            'agenda.eventos.ver',
            'agenda.eventos.crear',
            'agenda.eventos.editar',
            'agenda.eventos.eliminar',
        ];

        // Asignar al Rol Super Usuario
        // Buscar por nombre 'Super Usuario' o 'Administrador' según corresponda en este sistema
        $role = Role::where('nombre', 'Super Usuario')->first();

        if ($role) {
            $currentPermissions = $role->permisos ?? [];
            if (!is_array($currentPermissions)) {
                $currentPermissions = [];
            }

            // Merge unique
            $updatedPermissions = array_unique(array_merge($currentPermissions, $newPermissions));

            $role->permisos = array_values($updatedPermissions);
            $role->save();

            $this->command->info('Permisos de Agenda agregados al rol Super Usuario.');
        } else {
            $this->command->error('No se encontró el rol Super Usuario.');
        }
    }
}
