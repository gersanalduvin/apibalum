<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\ConfigGrado;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Configurar la aplicación Laravel
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

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRUEBA DE AUDITORÍA PARA CONFIG GRADO ===\n\n";

try {
    // 1. Autenticar un usuario para las pruebas
    $user = User::first();
    if (!$user) {
        echo "❌ No se encontró ningún usuario en la base de datos\n";
        exit(1);
    }
    
    Auth::login($user);
    echo "✅ Usuario autenticado: {$user->name} (ID: {$user->id})\n\n";

    // 2. Crear un registro de ConfigGrado
    echo "📝 Creando un nuevo ConfigGrado...\n";
    $configGrado = ConfigGrado::create([
        'uuid' => \Illuminate\Support\Str::uuid(),
        'nombre' => 'Primer Grado - Prueba Auditoría',
        'abreviatura' => '1G-PA',
        'orden' => 1,
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'version' => 1
    ]);
    
    echo "✅ ConfigGrado creado con ID: {$configGrado->id}\n";
    echo "   - Nombre: {$configGrado->nombre}\n";
    echo "   - Abreviatura: {$configGrado->abreviatura}\n";
    echo "   - Orden: {$configGrado->orden}\n\n";

    // 3. Verificar auditoría de creación
    $auditoriasCreacion = Audit::where('model_type', ConfigGrado::class)
        ->where('model_id', $configGrado->id)
        ->where('event', 'created')
        ->get();
    
    echo "🔍 Verificando auditoría de CREACIÓN:\n";
    if ($auditoriasCreacion->count() > 0) {
        echo "✅ Se registró {$auditoriasCreacion->count()} auditoría(s) de creación\n";
        foreach ($auditoriasCreacion as $audit) {
            echo "   - ID: {$audit->id}, Usuario: {$audit->user_id}, Evento: {$audit->event}\n";
            echo "   - Valores nuevos: " . json_encode($audit->new_values, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ No se registró auditoría de creación\n";
    }
    echo "\n";

    // 4. Actualizar el registro
    echo "📝 Actualizando el ConfigGrado...\n";
    $valoresAnteriores = [
        'nombre' => $configGrado->nombre,
        'abreviatura' => $configGrado->abreviatura,
        'orden' => $configGrado->orden
    ];
    
    $configGrado->update([
        'nombre' => 'Primer Grado - Actualizado',
        'abreviatura' => '1G-ACT',
        'orden' => 2,
        'updated_by' => $user->id,
        'version' => $configGrado->version + 1
    ]);
    
    echo "✅ ConfigGrado actualizado:\n";
    echo "   - Nombre: {$valoresAnteriores['nombre']} → {$configGrado->nombre}\n";
    echo "   - Abreviatura: {$valoresAnteriores['abreviatura']} → {$configGrado->abreviatura}\n";
    echo "   - Orden: {$valoresAnteriores['orden']} → {$configGrado->orden}\n\n";

    // 5. Verificar auditoría de actualización
    sleep(1); // Esperar un momento para asegurar que la auditoría se registre
    
    $auditoriasActualizacion = Audit::where('model_type', ConfigGrado::class)
        ->where('model_id', $configGrado->id)
        ->where('event', 'updated')
        ->get();
    
    echo "🔍 Verificando auditoría de ACTUALIZACIÓN:\n";
    if ($auditoriasActualizacion->count() > 0) {
        echo "✅ Se registró {$auditoriasActualizacion->count()} auditoría(s) de actualización\n";
        foreach ($auditoriasActualizacion as $audit) {
            echo "   - ID: {$audit->id}, Usuario: {$audit->user_id}, Evento: {$audit->event}\n";
            echo "   - Valores anteriores: " . json_encode($audit->old_values, JSON_PRETTY_PRINT) . "\n";
            echo "   - Valores nuevos: " . json_encode($audit->new_values, JSON_PRETTY_PRINT) . "\n";
            echo "   - Fecha: {$audit->created_at}\n";
        }
    } else {
        echo "❌ No se registró auditoría de actualización\n";
        echo "🔍 Verificando si hay algún problema...\n";
        
        // Verificar si el trait está funcionando
        $todasLasAuditorias = Audit::where('model_type', ConfigGrado::class)
            ->where('model_id', $configGrado->id)
            ->get();
        
        echo "📊 Total de auditorías para este ConfigGrado: {$todasLasAuditorias->count()}\n";
        foreach ($todasLasAuditorias as $audit) {
            echo "   - Evento: {$audit->event}, Fecha: {$audit->created_at}\n";
        }
    }
    echo "\n";

    // 6. Probar eliminación (soft delete)
    echo "📝 Eliminando el ConfigGrado (soft delete)...\n";
    $configGrado->update(['deleted_by' => $user->id]);
    $configGrado->delete();
    
    echo "✅ ConfigGrado eliminado (soft delete)\n\n";

    // 7. Verificar auditoría de eliminación
    sleep(1);
    
    $auditoriasEliminacion = Audit::where('model_type', ConfigGrado::class)
        ->where('model_id', $configGrado->id)
        ->where('event', 'deleted')
        ->get();
    
    echo "🔍 Verificando auditoría de ELIMINACIÓN:\n";
    if ($auditoriasEliminacion->count() > 0) {
        echo "✅ Se registró {$auditoriasEliminacion->count()} auditoría(s) de eliminación\n";
        foreach ($auditoriasEliminacion as $audit) {
            echo "   - ID: {$audit->id}, Usuario: {$audit->user_id}, Evento: {$audit->event}\n";
            echo "   - Valores anteriores: " . json_encode($audit->old_values, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ No se registró auditoría de eliminación\n";
    }
    echo "\n";

    // 8. Resumen final
    $totalAuditorias = Audit::where('model_type', ConfigGrado::class)
        ->where('model_id', $configGrado->id)
        ->count();
    
    echo "=== RESUMEN FINAL ===\n";
    echo "📊 Total de auditorías registradas para ConfigGrado ID {$configGrado->id}: {$totalAuditorias}\n";
    
    if ($totalAuditorias >= 3) {
        echo "✅ La auditoría está funcionando correctamente\n";
    } else {
        echo "❌ La auditoría no está funcionando completamente\n";
        echo "   Se esperaban al menos 3 auditorías (created, updated, deleted)\n";
    }

    // 9. Verificar configuración del modelo
    echo "\n=== VERIFICACIÓN DE CONFIGURACIÓN ===\n";
    $reflection = new ReflectionClass(ConfigGrado::class);
    
    // Verificar si usa el trait Auditable
    $traits = $reflection->getTraitNames();
    echo "🔍 Traits utilizados: " . implode(', ', $traits) . "\n";
    
    if (in_array('App\Traits\Auditable', $traits)) {
        echo "✅ El trait Auditable está configurado\n";
    } else {
        echo "❌ El trait Auditable NO está configurado\n";
    }
    
    // Verificar propiedades de auditoría
    $configGradoInstance = new ConfigGrado();
    
    if (property_exists($configGradoInstance, 'auditableFields')) {
        $auditableFields = $reflection->getProperty('auditableFields');
        $auditableFields->setAccessible(true);
        $fields = $auditableFields->getValue($configGradoInstance);
        echo "✅ Campos auditables configurados: " . implode(', ', $fields) . "\n";
    }
    
    if (property_exists($configGradoInstance, 'auditableEvents')) {
        $auditableEvents = $reflection->getProperty('auditableEvents');
        $auditableEvents->setAccessible(true);
        $events = $auditableEvents->getValue($configGradoInstance);
        echo "✅ Eventos auditables configurados: " . implode(', ', $events) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
    echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";