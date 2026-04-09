<?php

namespace App\Console\Commands;

use App\Services\PermissionService;
use Illuminate\Console\Command;

class TestCategorizedPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-categorized-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la nueva estructura de permisos categorizados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $permissionService = new PermissionService();

        $this->info('=== ESTRUCTURA DE PERMISOS CATEGORIZADOS ===');
        $this->newLine();

        // Mostrar todas las categorías
        $this->info('📁 CATEGORÍAS DISPONIBLES:');
        $categories = $permissionService->getCategories();
        foreach ($categories as $category) {
            $this->line("  - " . ucfirst($category));
        }
        $this->newLine();

        // Mostrar permisos agrupados por categoría
        $this->info('🔐 PERMISOS AGRUPADOS POR CATEGORÍA:');
        $groupedPermissions = $permissionService->getGroupedPermissions();
        
        foreach ($groupedPermissions as $category => $categoryData) {
            $this->warn("\n📂 {$categoryData['category_name']}:");
            
            foreach ($categoryData['modules'] as $module => $moduleData) {
                $this->line("  📄 {$moduleData['module_name']}:");
                
                foreach ($moduleData['permissions'] as $permission) {
                    $this->line("    ✓ {$permission['display_name']} ({$permission['permission']})");
                }
            }
        }
        $this->newLine();

        // Probar búsqueda de permisos específicos
        $this->info('🔍 PRUEBAS DE BÚSQUEDA:');
        
        // Buscar permisos de roles en configuración
        $rolesPermissions = $permissionService->getModulePermissions('roles', 'configuracion');
        $this->line("Permisos de roles en configuración: " . count($rolesPermissions) . " encontrados");
        
        // Buscar permisos de roles sin especificar categoría
        $rolesPermissionsAuto = $permissionService->getModulePermissions('roles');
        $this->line("Permisos de roles (búsqueda automática): " . count($rolesPermissionsAuto) . " encontrados");
        
        // Verificar si existe un permiso específico
        $exists = $permissionService->permissionExists('roles', 'ver', 'configuracion');
        $this->line("¿Existe roles.ver en configuración? " . ($exists ? 'Sí' : 'No'));
        
        $this->newLine();

        // Mostrar permisos por acción
        $this->info('⚡ PERMISOS POR ACCIÓN "VER":');
        $verPermissions = $permissionService->getPermissionsByAction('ver');
        foreach ($verPermissions as $permission) {
            $this->line("  - {$permission['display_name']} ({$permission['permission']})");
        }
        $this->newLine();

        // Mostrar módulos de una categoría específica
        $this->info('📋 MÓDULOS EN CATEGORÍA "CONFIGURACIÓN":');
        $configModules = $permissionService->getCategoryModules('configuracion');
        foreach ($configModules as $module) {
            $this->line("  - " . ucfirst($module));
        }
        $this->newLine();

        // Mostrar permisos planos
        $this->info('📊 RESUMEN DE PERMISOS PLANOS:');
        $flatPermissions = $permissionService->getFlatPermissions();
        $this->line("Total de permisos: " . count($flatPermissions));
        
        $categoryCounts = [];
        foreach ($flatPermissions as $permission) {
            $category = $permission['category'];
            $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
        }
        
        foreach ($categoryCounts as $category => $count) {
            $this->line("  - " . ucfirst($category) . ": {$count} permisos");
        }

        $this->newLine();
        $this->info('✅ Prueba completada exitosamente');
    }
}
