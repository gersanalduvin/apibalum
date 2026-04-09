<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;

class MigratePermissions extends Command
{
    protected $signature = 'app:migrate-permissions';
    protected $description = 'Migrate permissions for Consolidado de Notas';

    public function handle()
    {
        $roles = Role::all();

        foreach ($roles as $role) {
            $permisos = $role->permisos ?? [];

            if (in_array('generar.boletin', $permisos) && !in_array('ver.actividades_semana', $permisos)) {
                $permisos[] = 'ver.actividades_semana';
                $role->permisos = $permisos;
                $role->save();
                $this->info("Granted to " . $role->nombre);
            }
        }
        $this->info("Migration completed");
    }
}
