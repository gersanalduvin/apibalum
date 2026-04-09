<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Services\PermissionService;

class AssignRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-role-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign role permissions to admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== ASIGNANDO PERMISOS DE ROLES ===');
        
        // Obtener usuario admin
        $admin = User::where('email', 'admin@test.com')->first();
        if (!$admin) {
            $this->error('Usuario admin no encontrado');
            return;
        }
        
        // Obtener rol del admin
        $role = $admin->role;
        if (!$role) {
            $this->error('El usuario admin no tiene rol asignado');
            return;
        }
        
        $this->info('Usuario: ' . $admin->name);
        $this->info('Rol actual: ' . $role->nombre);
        $this->info('Permisos actuales: ' . json_encode($role->permisos));
        
        // Obtener permisos de roles del PermissionService
        $permissionService = new PermissionService();
        $rolePermissions = $permissionService->getModulePermissions('roles');
        
        // Convertir a array de valores
        $permissionsArray = array_values($rolePermissions);
        
        // Asignar permisos al rol
        $role->permisos = $permissionsArray;
        $role->save();
        
        $this->info('\n=== PERMISOS ASIGNADOS ===');
        foreach ($permissionsArray as $permission) {
            $this->line('- ' . $permission);
        }
        
        $this->info('\nPermisos asignados exitosamente al rol: ' . $role->nombre);
        
        // Generar nuevo token
        $token = $admin->createToken('test-token-with-permissions')->plainTextToken;
        $this->info('\nNuevo token generado: ' . $token);
        
        // Mostrar comando para probar
        $this->info('\n=== COMANDO PARA PROBAR ===');
        $this->line('Invoke-WebRequest -Uri "http://localhost:8000/api/v1/roles" -Method GET -Headers @{"Accept"="application/json"; "Authorization"="Bearer ' . $token . '"} | Select-Object StatusCode, Content');
    }
}
