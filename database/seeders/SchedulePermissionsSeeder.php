<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SchedulePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permisos del Generador de Horarios (Claves exactas de PermissionService)
        $newPermissions = [
            'configuracion_academica.horarios.ver',
            'configuracion_academica.horarios.generar',
            'configuracion_academica.horarios.editar',
            'configuracion_academica.horarios.configurar',
            'configuracion_academica.horarios.eliminar',
            'configuracion_academica.horarios.exportar',
        ];

        // Asignar al Rol Super Usuario
        $role = \App\Models\Role::where('nombre', 'Super Usuario')->first(); // Note: 'nombre' based on Role model logic

        if ($role) {
            $currentPermissions = $role->permisos ?? [];
            if (!is_array($currentPermissions)) {
                $currentPermissions = [];
            }

            // Merge unqique
            $updatedPermissions = array_unique(array_merge($currentPermissions, $newPermissions));

            $role->permisos = array_values($updatedPermissions);
            $role->save();
        }

        // Opcional: Asignar schedule.view a Docentes
        // Note: Assuming 'Docente' role exists and follows same pattern
    }
}
