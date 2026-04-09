<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class LessonPlansPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teacherPermissions = [
            'agenda.planes_clases.ver',
            'agenda.planes_clases.crear',
            'agenda.planes_clases.editar',
            'agenda.planes_clases.eliminar',
        ];

        $adminPermissions = array_merge($teacherPermissions, [
            'agenda.planes_clases.ver_todos',
        ]);

        // 1. Super Usuario / Admin
        $adminRole = Role::where('nombre', 'Super Usuario')->first();
        if ($adminRole) {
            $current = $adminRole->permisos ?? [];
            if (!is_array($current)) $current = [];
            $updated = array_unique(array_merge($current, $adminPermissions));
            $adminRole->permisos = array_values($updated);
            $adminRole->save();
            $this->command->info("Permisos de Planes de Clases agregados a Super Usuario.");
        }

        // 2. Docente
        $teacherRole = Role::where('nombre', 'Docente')->first();
        if ($teacherRole) {
            $current = $teacherRole->permisos ?? [];
            if (!is_array($current)) $current = [];
            $updated = array_unique(array_merge($current, $teacherPermissions));
            $teacherRole->permisos = array_values($updated);
            $teacherRole->save();
            $this->command->info("Permisos de Planes de Clases agregados a Docente.");
        } else {
            $this->command->warn("No se encontró el rol Docente.");
        }
    }
}
