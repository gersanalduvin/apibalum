<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class DocentePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = 'operaciones.docentes';

        // 1. Assign to Super Usuario
        $adminRole = Role::where('nombre', 'Super Usuario')->first();
        if ($adminRole) {
            $perms = $adminRole->permisos ?? [];
            if (!in_array($permission, $perms)) {
                $perms[] = $permission;
                $adminRole->permisos = $perms;
                $adminRole->save();
                $this->command->info("Permiso '$permission' agregado a Super Usuario.");
            }
        }

        // 2. Assign to Docente
        $docenteRole = Role::where('nombre', 'Docente')->first();
        if ($docenteRole) {
            $perms = $docenteRole->permisos ?? [];
            if (!in_array($permission, $perms)) {
                $perms[] = $permission;
                $docenteRole->permisos = $perms;
                $docenteRole->save();
                $this->command->info("Permiso '$permission' agregado a Rol Docente.");
            }
        } else {
            $this->command->error("No se encontró el rol 'Docente'.");
        }
    }
}
