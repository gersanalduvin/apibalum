<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;
use App\Models\ConfigArancel;
use App\Models\User;

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

// Inicializar la aplicación
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRUEBA DE AUDITORÍA PARA CONFIGARANCEL ===\n\n";

try {
    // Autenticar un usuario para las pruebas
    $user = User::first();
    if (!$user) {
        echo "❌ No se encontró ningún usuario en la base de datos\n";
        exit(1);
    }
    
    auth()->login($user);
    echo "✅ Usuario autenticado: {$user->email}\n\n";

    // 1. Crear un nuevo arancel
    echo "1. Creando nuevo arancel...\n";
    $arancel = ConfigArancel::create([
        'codigo' => 'TEST-' . substr(time(), -6),
        'nombre' => 'Arancel de Prueba Auditoría',
        'precio' => 100.00,
        'moneda' => true,
        'activo' => true
    ]);
    
    echo "✅ Arancel creado con ID: {$arancel->id}\n";
    
    // Verificar auditoría de creación
    $auditoriasCreacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigArancel')
        ->where('model_id', $arancel->id)
        ->where('event', 'created')
        ->get();
    
    echo "📊 Auditorías de creación encontradas: " . $auditoriasCreacion->count() . "\n";
    if ($auditoriasCreacion->count() > 0) {
        echo "✅ Auditoría de creación registrada correctamente\n";
    } else {
        echo "❌ No se registró auditoría de creación\n";
    }
    
    echo "\n2. Actualizando arancel...\n";
    
    // 2. Actualizar el arancel
    $arancel->update([
        'nombre' => 'Arancel Actualizado - ' . time(),
        'precio' => 150.00
    ]);
    
    echo "✅ Arancel actualizado\n";
    
    // Verificar auditoría de actualización
    $auditoriasActualizacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigArancel')
        ->where('model_id', $arancel->id)
        ->where('event', 'updated')
        ->get();
    
    echo "📊 Auditorías de actualización encontradas: " . $auditoriasActualizacion->count() . "\n";
    
    if ($auditoriasActualizacion->count() > 0) {
        echo "✅ Auditoría de actualización registrada correctamente\n";
        
        // Mostrar detalles de la última auditoría
        $ultimaAuditoria = $auditoriasActualizacion->last();
        echo "📋 Detalles de la auditoría:\n";
        echo "   - Usuario ID: {$ultimaAuditoria->user_id}\n";
        echo "   - Evento: {$ultimaAuditoria->event}\n";
        echo "   - Fecha: {$ultimaAuditoria->created_at}\n";
        
        $oldValues = json_decode($ultimaAuditoria->old_values, true);
        $newValues = json_decode($ultimaAuditoria->new_values, true);
        
        echo "   - Valores anteriores: " . json_encode($oldValues, JSON_PRETTY_PRINT) . "\n";
        echo "   - Valores nuevos: " . json_encode($newValues, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ No se registró auditoría de actualización\n";
    }
    
    echo "\n3. Eliminando arancel...\n";
    
    // 3. Eliminar el arancel (soft delete)
    $arancel->delete();
    
    echo "✅ Arancel eliminado (soft delete)\n";
    
    // Verificar auditoría de eliminación
    $auditoriasEliminacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigArancel')
        ->where('model_id', $arancel->id)
        ->where('event', 'deleted')
        ->get();
    
    echo "📊 Auditorías de eliminación encontradas: " . $auditoriasEliminacion->count() . "\n";
    
    if ($auditoriasEliminacion->count() > 0) {
        echo "✅ Auditoría de eliminación registrada correctamente\n";
    } else {
        echo "❌ No se registró auditoría de eliminación\n";
    }
    
    // Resumen final
    echo "\n=== RESUMEN ===\n";
    $totalAuditorias = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigArancel')
        ->where('model_id', $arancel->id)
        ->count();
    
    echo "📊 Total de auditorías para este arancel: {$totalAuditorias}\n";
    
    if ($totalAuditorias >= 3) {
        echo "✅ ¡Auditoría funcionando correctamente!\n";
    } else {
        echo "❌ Hay problemas con la auditoría\n";
    }

} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";