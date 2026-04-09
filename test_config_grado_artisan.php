<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\ConfigGrado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

// Inicializar Laravel completamente
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE AUDITORÍA CONFIGGRADO VIA CONTROLADOR ===\n\n";

try {
    // 1. Obtener un usuario existente
    $user = User::first();
    if (!$user) {
        echo "❌ No se encontró ningún usuario en la base de datos\n";
        exit(1);
    }
    
    // Autenticar el usuario
    Auth::login($user);
    echo "✓ Usuario autenticado: {$user->email} (ID: {$user->id})\n";

    // 2. Crear un grado inicial directamente
    echo "\n--- CREACIÓN DIRECTA ---\n";
    
    $grado = ConfigGrado::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'nombre' => 'Grado Prueba Artisan',
        'abreviatura' => 'GPA',
        'orden' => 999,
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'is_synced' => true,
        'version' => 1
    ]);
    
    echo "✓ Grado creado - ID: {$grado->id}\n";
    echo "  - Nombre: {$grado->nombre}\n";
    echo "  - Abreviatura: {$grado->abreviatura}\n";
    echo "  - Orden: {$grado->orden}\n";

    // 3. Verificar auditoría de creación
    $auditoriasCreacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $grado->id)
        ->where('event', 'created')
        ->get();
    
    echo "✓ Auditorías de creación encontradas: " . $auditoriasCreacion->count() . "\n";

    // 4. Actualizar usando el servicio (simulando controlador)
    echo "\n--- ACTUALIZACIÓN VIA SERVICIO ---\n";
    
    $configGradoService = app(\App\Services\ConfigGradoService::class);
    
    $updateData = [
        'nombre' => 'Grado Actualizado Artisan',
        'abreviatura' => 'GAA',
        'orden' => 888
    ];
    
    $gradoActualizado = $configGradoService->updateConfigGrado($grado->id, $updateData);
    
    echo "✓ Grado actualizado via servicio\n";
    echo "  - Nombre: {$gradoActualizado->nombre}\n";
    echo "  - Abreviatura: {$gradoActualizado->abreviatura}\n";
    echo "  - Orden: {$gradoActualizado->orden}\n";
    echo "  - Updated by: {$gradoActualizado->updated_by}\n";
    echo "  - Version: {$gradoActualizado->version}\n";

    // 5. Verificar auditoría de actualización
    echo "\n--- VERIFICACIÓN DE AUDITORÍA DE ACTUALIZACIÓN ---\n";
    
    $auditoriasActualizacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $grado->id)
        ->where('event', 'updated')
        ->get();
    
    echo "✓ Auditorías de actualización encontradas: " . $auditoriasActualizacion->count() . "\n";
    
    if ($auditoriasActualizacion->count() > 0) {
        foreach ($auditoriasActualizacion as $auditoria) {
            echo "  - ID: {$auditoria->id}\n";
            echo "  - Usuario: {$auditoria->user_id}\n";
            echo "  - Evento: {$auditoria->event}\n";
            echo "  - Tabla: {$auditoria->table_name}\n";
            echo "  - Columna: {$auditoria->column_name}\n";
            echo "  - Valor anterior: {$auditoria->old_value}\n";
            echo "  - Valor nuevo: {$auditoria->new_value}\n";
            echo "  - Fecha: {$auditoria->created_at}\n";
            echo "  ---\n";
        }
    } else {
        echo "❌ NO se encontraron auditorías de actualización\n";
        
        // Diagnóstico adicional
        echo "\n--- DIAGNÓSTICO ---\n";
        
        // Verificar configuración del trait
        $traits = class_uses($grado);
        echo "✓ Traits del modelo: " . implode(', ', array_keys($traits)) . "\n";
        
        if (in_array('App\\Traits\\Auditable', $traits)) {
            echo "✓ Trait Auditable está presente\n";
            
            // Verificar propiedades del trait
            $reflection = new ReflectionClass($grado);
            $properties = $reflection->getDefaultProperties();
            
            echo "✓ Campos auditables: " . json_encode($properties['auditableFields'] ?? []) . "\n";
            echo "✓ Eventos auditables: " . json_encode($properties['auditableEvents'] ?? []) . "\n";
            echo "✓ Auditoría granular: " . ($properties['granularAudit'] ?? 'false') . "\n";
        } else {
            echo "❌ Trait Auditable NO está presente\n";
        }
        
        // Verificar si el usuario está autenticado durante la actualización
        echo "✓ Usuario autenticado durante actualización: " . (Auth::check() ? Auth::id() : 'NO') . "\n";
    }

    // 6. Mostrar todas las auditorías del grado
    echo "\n--- TODAS LAS AUDITORÍAS DEL GRADO ---\n";
    $todasAuditorias = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $grado->id)
        ->orderBy('created_at')
        ->get();
    
    echo "✓ Total de auditorías: " . $todasAuditorias->count() . "\n";
    
    foreach ($todasAuditorias as $auditoria) {
        echo "  - Evento: {$auditoria->event} | Columna: {$auditoria->column_name} | Usuario: {$auditoria->user_id} | Fecha: {$auditoria->created_at}\n";
    }

    // 7. Probar actualización directa del modelo
    echo "\n--- ACTUALIZACIÓN DIRECTA DEL MODELO ---\n";
    
    $grado->nombre = 'Grado Directo';
    $grado->save();
    
    echo "✓ Actualización directa realizada\n";
    
    // Verificar auditoría de actualización directa
    $auditoriasDirectas = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $grado->id)
        ->where('event', 'updated')
        ->where('new_value', 'Grado Directo')
        ->get();
    
    echo "✓ Auditorías de actualización directa: " . $auditoriasDirectas->count() . "\n";

    // 8. Limpiar - eliminar el grado de prueba
    echo "\n--- LIMPIEZA ---\n";
    $grado->delete();
    echo "✓ Grado eliminado\n";
    
    // Verificar auditoría de eliminación
    $auditoriasEliminacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $grado->id)
        ->where('event', 'deleted')
        ->get();
    
    echo "✓ Auditorías de eliminación: " . $auditoriasEliminacion->count() . "\n";

    // 9. Resumen final
    echo "\n=== RESUMEN FINAL ===\n";
    $totalAuditoriasFinal = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $grado->id)
        ->count();
    
    echo "✓ Total de auditorías registradas para el grado: {$totalAuditoriasFinal}\n";
    
    $totalActualizaciones = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $grado->id)
        ->where('event', 'updated')
        ->count();
    
    if ($totalActualizaciones > 0) {
        echo "✅ SUCCESS: La auditoría de actualización SÍ funciona ({$totalActualizaciones} registros)\n";
    } else {
        echo "❌ PROBLEM: La auditoría de actualización NO funciona\n";
    }

} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";