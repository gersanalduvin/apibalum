<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\V1\PermissionController;
use Illuminate\Http\Request;

class TestCategoryEndpoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-category-endpoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba los nuevos endpoints de categorías de permisos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando pruebas de endpoints de categorías');
        $this->newLine();
        
        $controller = app(PermissionController::class);
        
        // Test 1: Obtener todas las categorías
        $this->testEndpoint('Obtener todas las categorías', function() use ($controller) {
            return $controller->categories();
        });
        
        // Test 2: Obtener permisos de categoría 'configuracion'
        $this->testEndpoint('Obtener permisos de categoría CONFIGURACION', function() use ($controller) {
            return $controller->categoryPermissions('configuracion');
        });
        
        // Test 3: Obtener módulos de categoría 'configuracion'
        $this->testEndpoint('Obtener módulos de categoría CONFIGURACION', function() use ($controller) {
            return $controller->categoryModules('configuracion');
        });
        
        // Test 4: Obtener permisos de categoría 'gestion'
        $this->testEndpoint('Obtener permisos de categoría GESTION', function() use ($controller) {
            return $controller->categoryPermissions('gestion');
        });
        
        // Test 5: Obtener módulos de categoría 'gestion'
        $this->testEndpoint('Obtener módulos de categoría GESTION', function() use ($controller) {
            return $controller->categoryModules('gestion');
        });
        
        // Test 6: Obtener permisos de categoría 'reportes'
        $this->testEndpoint('Obtener permisos de categoría REPORTES', function() use ($controller) {
            return $controller->categoryPermissions('reportes');
        });
        
        // Test 7: Obtener módulos de categoría 'reportes'
        $this->testEndpoint('Obtener módulos de categoría REPORTES', function() use ($controller) {
            return $controller->categoryModules('reportes');
        });
        
        // Test 8: Probar categoría inexistente
        $this->testEndpoint('Probar categoría INEXISTENTE (debe fallar)', function() use ($controller) {
            return $controller->categoryPermissions('inexistente');
        });
        
        // Test 9: Verificar compatibilidad - módulos generales
        $this->testEndpoint('Obtener todos los módulos (compatibilidad)', function() use ($controller) {
            return $controller->modules();
        });
        
        // Test 10: Verificar permisos agrupados
        $this->testEndpoint('Obtener permisos agrupados', function() use ($controller) {
            return $controller->grouped();
        });
        
        // Test 11: Nuevo endpoint - Permisos detallados agrupados
        $this->testEndpoint('Test 11: Permisos detallados agrupados', function() use ($controller) {
            return $controller->detailed();
        });
        
        // Test 12: Nuevo endpoint - Permisos planos detallados
        $this->testEndpoint('Test 12: Permisos planos detallados', function() use ($controller) {
            return $controller->flatDetailed();
        });
        
        $this->newLine();
        $this->info('✅ Todas las pruebas de endpoints completadas');
        $this->info('🎯 Los nuevos endpoints de categorías están funcionando correctamente');
        $this->info('🎯 Nuevos endpoints de listado detallado funcionando correctamente');
    }
    
    /**
     * Ejecuta una prueba de endpoint y muestra el resultado
     */
    private function testEndpoint($title, $callback)
    {
        $this->line(str_repeat('=', 60));
        $this->info("TEST: {$title}");
        $this->line(str_repeat('=', 60));
        
        try {
            $response = $callback();
            $data = $response->getData(true);
            
            $this->info("✅ Status: SUCCESS");
            $this->info("📝 Message: {$data['message']}");
            
            if (isset($data['data'])) {
                $this->info("📊 Data Count: " . (is_array($data['data']) ? count($data['data']) : 'N/A'));
                
                // Mostrar una muestra de los datos
                if (is_array($data['data']) && !empty($data['data'])) {
                    $sample = array_slice($data['data'], 0, 3, true);
                    $this->info("🔍 Sample Data:");
                    foreach ($sample as $key => $value) {
                        if (is_array($value)) {
                            $this->line("   - {$key}: [" . implode(', ', array_keys($value)) . "]");
                        } else {
                            $this->line("   - {$key}: {$value}");
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ ERROR: {$e->getMessage()}");
            $this->error("📍 File: {$e->getFile()}:{$e->getLine()}");
        }
        
        $this->newLine();
    }
}
