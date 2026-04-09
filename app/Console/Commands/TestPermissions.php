<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Services\PermissionService;

class TestPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test permissions and generate token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== VERIFICANDO DATOS ===');
        
        // Verificar roles
        $roles = Role::all();
        $this->info('Roles encontrados: ' . $roles->count());
        foreach ($roles as $role) {
            $this->line('- ' . $role->name);
        }
        
        // Verificar permisos de roles
        $permissionService = new PermissionService();
        $rolePermissions = $permissionService->getModulePermissions('roles');
        $this->info('\nPermisos de roles encontrados: ' . count($rolePermissions));
        foreach ($rolePermissions as $action => $permission) {
            $this->line('- ' . $permission);
        }
        
        // Verificar usuario admin
        $admin = User::where('email', 'admin@test.com')->first();
        if ($admin) {
            $this->info('\nUsuario admin encontrado: ' . $admin->name);
            $this->info('Role ID: ' . $admin->role_id);
            
            // Crear token
            $token = $admin->createToken('test-token')->plainTextToken;
            $this->info('\nToken generado: ' . $token);
            
            // Mostrar comando curl para probar
            $this->info('\n=== COMANDO PARA PROBAR ===');
            $this->line('curl -X GET "http://localhost:8000/api/v1/roles" -H "Accept: application/json" -H "Authorization: Bearer ' . $token . '"');
        } else {
            $this->error('Usuario admin no encontrado');
        }
    }
}
