<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ConfigArancel;
use App\Models\InventarioKardex;
use App\Models\InventarioMovimiento;
use App\Models\ConfigCatalogoCuentas;
use App\Models\Audit;

// Crear la aplicación Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE LIMPIEZA DE AUDITORÍA LEGACY ===\n\n";

try {
    // Autenticar usuario
    $user = User::first();
    if (!$user) {
        echo "❌ No se encontró ningún usuario para autenticar\n";
        exit(1);
    }
    
    Auth::login($user);
    echo "✅ Usuario autenticado: {$user->email}\n\n";

    // Función para probar auditoría de un modelo
    function testModelAudit($modelClass, $testData, $updateData) {
        echo "--- Probando auditoría para: " . class_basename($modelClass) . " ---\n";
        
        // Crear registro
        $model = $modelClass::create($testData);
        echo "✅ Registro creado con ID: {$model->id}\n";
        
        // Actualizar registro
        $model->update($updateData);
        echo "✅ Registro actualizado\n";
        
        // Eliminar registro (soft delete)
        $model->delete();
        echo "✅ Registro eliminado (soft delete)\n";
        
        // Verificar auditorías
        $audits = Audit::where('model_type', $modelClass)
                      ->where('model_id', $model->id)
                      ->get();
        
        echo "📊 Total de auditorías registradas: " . $audits->count() . "\n";
        
        $events = $audits->pluck('event')->toArray();
        echo "📋 Eventos registrados: " . implode(', ', $events) . "\n";
        
        // Verificar que se registraron los 3 eventos principales
        $expectedEvents = ['created', 'updated', 'deleted'];
        $missingEvents = array_diff($expectedEvents, $events);
        
        if (empty($missingEvents)) {
            echo "✅ Todos los eventos de auditoría se registraron correctamente\n";
        } else {
            echo "❌ Eventos faltantes: " . implode(', ', $missingEvents) . "\n";
        }
        
        echo "\n";
        return $audits->count() >= 3;
    }

    $allTestsPassed = true;

    // Test 1: ConfigArancel
    $testPassed = testModelAudit(
        ConfigArancel::class,
        [
            'codigo' => 'TEST-' . substr(time(), -6),
            'nombre' => 'Arancel de Prueba',
            'precio' => 100.00, // Campo requerido según la migración
            'activo' => true
        ],
        [
            'precio' => 150.00,
            'nombre' => 'Arancel actualizado'
        ]
    );
    $allTestsPassed = $allTestsPassed && $testPassed;

    // Test 2: InventarioMovimiento (primero, porque InventarioKardex depende de él)
    $testPassed = testModelAudit(
        InventarioMovimiento::class,
        [
            'producto_id' => 1,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 5.0000,
            'costo_unitario' => 25.00,
            'costo_total' => 125.00,
            'stock_anterior' => 0.0000,
            'stock_posterior' => 5.0000,
            'observaciones' => 'Movimiento de prueba'
        ],
        [
            'cantidad' => 8.0000,
            'observaciones' => 'Movimiento actualizado'
        ]
    );
    $allTestsPassed = $allTestsPassed && $testPassed;

    // Crear un movimiento para usar en InventarioKardex
    $movimientoParaKardex = InventarioMovimiento::create([
        'producto_id' => 1,
        'tipo_movimiento' => 'entrada',
        'cantidad' => 1.0000,
        'costo_unitario' => 10.00,
        'costo_total' => 10.00,
        'stock_anterior' => 0.0000,
        'stock_posterior' => 1.0000,
        'observaciones' => 'Movimiento para Kardex'
    ]);

    // Test 3: InventarioKardex (usando el movimiento creado)
    $testPassed = testModelAudit(
        InventarioKardex::class,
        [
            'producto_id' => 1,
            'movimiento_id' => $movimientoParaKardex->id,
            'tipo_movimiento' => 'entrada',
            'cantidad' => 10.0000,
            'costo_unitario' => 50.00,
            'stock_anterior' => 0.0000,
            'valor_anterior' => 0.0000,
            'costo_promedio_anterior' => 0.0000,
            'valor_movimiento' => 500.00,
            'stock_posterior' => 10.0000,
            'valor_posterior' => 500.00,
            'costo_promedio_posterior' => 50.00,
            'periodo_year' => 2025,
            'periodo_month' => 1,
            'fecha_movimiento' => '2025-01-15',
            'observaciones' => 'Entrada de prueba'
        ],
        [
            'cantidad' => 15.0000,
            'observaciones' => 'Entrada actualizada'
        ]
    );
    $allTestsPassed = $allTestsPassed && $testPassed;

    // Test 4: ConfigCatalogoCuentas
    $testPassed = testModelAudit(
        ConfigCatalogoCuentas::class,
        [
            'codigo' => 'TEST-' . substr(time(), -6),
            'nombre' => 'Cuenta de Prueba',
            'tipo_cuenta' => 'activo',
            'nivel' => 1,
            'activo' => true
        ],
        [
            'nombre' => 'Cuenta Actualizada',
            'tipo_cuenta' => 'pasivo'
        ]
    );
    $allTestsPassed = $allTestsPassed && $testPassed;

    // Resumen final
    echo "=== RESUMEN DE VERIFICACIÓN ===\n";
    if ($allTestsPassed) {
        echo "✅ TODAS LAS PRUEBAS PASARON EXITOSAMENTE\n";
        echo "✅ La limpieza del código legacy de auditoría fue exitosa\n";
        echo "✅ El trait Auditable está funcionando correctamente en todos los modelos\n";
    } else {
        echo "❌ ALGUNAS PRUEBAS FALLARON\n";
        echo "❌ Revisar la configuración de auditoría en los modelos que fallaron\n";
    }

    // Estadísticas finales
    $totalAudits = Audit::count();
    echo "\n📊 Total de auditorías en la base de datos: {$totalAudits}\n";

} catch (Exception $e) {
    echo "❌ Error durante la verificación: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";