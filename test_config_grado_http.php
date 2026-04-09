<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\ConfigGrado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

echo "=== PRUEBA DE AUDITORÍA CONFIGGRADO VIA HTTP ===\n\n";

try {
    // 1. Crear un usuario de prueba para autenticación
    $user = User::first();
    if (!$user) {
        $user = User::create([
            'name' => 'Usuario Prueba',
            'email' => 'prueba@test.com',
            'password' => bcrypt('password'),
            'created_by' => 1,
            'updated_by' => 1
        ]);
    }
    
    echo "✓ Usuario encontrado: {$user->email}\n";

    // 2. Crear un grado inicial usando petición HTTP POST
    echo "\n--- CREACIÓN VIA HTTP POST ---\n";
    
    $createData = [
        'nombre' => 'Grado Prueba HTTP',
        'abreviatura' => 'GPH',
        'orden' => 999
    ];
    
    // Crear request HTTP POST
    $createRequest = Request::create('/api/v1/config-grado', 'POST', $createData);
    $createRequest->headers->set('Accept', 'application/json');
    $createRequest->headers->set('Content-Type', 'application/json');
    
    // Simular autenticación
    Auth::login($user);
    
    // Procesar la petición
    $createResponse = $kernel->handle($createRequest);
    $createResponseData = json_decode($createResponse->getContent(), true);
    
    if (!$createResponseData || !$createResponseData['success']) {
        throw new Exception('Error al crear grado: ' . ($createResponseData['message'] ?? 'Respuesta inválida'));
    }
    
    $gradoId = $createResponseData['data']['id'];
    echo "✓ Grado creado via HTTP - ID: {$gradoId}\n";
    echo "  - Nombre: {$createResponseData['data']['nombre']}\n";
    echo "  - Abreviatura: {$createResponseData['data']['abreviatura']}\n";
    echo "  - Orden: {$createResponseData['data']['orden']}\n";

    // 3. Verificar auditoría de creación
    $auditoriasCreacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $gradoId)
        ->where('event', 'created')
        ->get();
    
    echo "✓ Auditorías de creación encontradas: " . $auditoriasCreacion->count() . "\n";
    
    // 4. Actualizar el grado usando petición HTTP PUT
    echo "\n--- ACTUALIZACIÓN VIA HTTP PUT ---\n";
    
    $updateData = [
        'nombre' => 'Grado Actualizado HTTP',
        'abreviatura' => 'GAH',
        'orden' => 888
    ];
    
    // Crear request HTTP PUT
    $updateRequest = Request::create("/api/v1/config-grado/{$gradoId}", 'PUT', $updateData);
    $updateRequest->headers->set('Accept', 'application/json');
    $updateRequest->headers->set('Content-Type', 'application/json');
    
    // Asegurar que el usuario sigue autenticado
    Auth::login($user);
    
    // Procesar la petición
    $updateResponse = $kernel->handle($updateRequest);
    $updateResponseData = json_decode($updateResponse->getContent(), true);
    
    echo "Status Code: " . $updateResponse->getStatusCode() . "\n";
    echo "Response: " . $updateResponse->getContent() . "\n";
    
    if (!$updateResponseData || !$updateResponseData['success']) {
        throw new Exception('Error al actualizar grado: ' . ($updateResponseData['message'] ?? 'Respuesta inválida'));
    }
    
    echo "✓ Grado actualizado via HTTP\n";
    echo "  - Nombre: {$updateResponseData['data']['nombre']}\n";
    echo "  - Abreviatura: {$updateResponseData['data']['abreviatura']}\n";
    echo "  - Orden: {$updateResponseData['data']['orden']}\n";

    // 5. Verificar auditoría de actualización
    echo "\n--- VERIFICACIÓN DE AUDITORÍA DE ACTUALIZACIÓN ---\n";
    
    // Esperar un momento para asegurar que la auditoría se registre
    sleep(1);
    
    $auditoriasActualizacion = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $gradoId)
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
        
        // Verificar si hay algún problema con la configuración
        echo "\n--- DIAGNÓSTICO ---\n";
        $grado = ConfigGrado::find($gradoId);
        if ($grado) {
            echo "✓ Grado existe en BD\n";
            echo "  - Updated by: {$grado->updated_by}\n";
            echo "  - Updated at: {$grado->updated_at}\n";
            echo "  - Version: {$grado->version}\n";
            
            // Verificar si el trait Auditable está configurado
            $traits = class_uses($grado);
            echo "  - Traits: " . implode(', ', array_keys($traits)) . "\n";
            
            if (in_array('App\\Traits\\Auditable', $traits)) {
                echo "  ✓ Trait Auditable está presente\n";
            } else {
                echo "  ❌ Trait Auditable NO está presente\n";
            }
        }
    }

    // 6. Mostrar todas las auditorías del grado
    echo "\n--- TODAS LAS AUDITORÍAS DEL GRADO ---\n";
    $todasAuditorias = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $gradoId)
        ->orderBy('created_at')
        ->get();
    
    echo "✓ Total de auditorías: " . $todasAuditorias->count() . "\n";
    
    foreach ($todasAuditorias as $auditoria) {
        echo "  - Evento: {$auditoria->event} | Columna: {$auditoria->column_name} | Fecha: {$auditoria->created_at}\n";
    }

    // 7. Limpiar - eliminar el grado de prueba
    echo "\n--- LIMPIEZA ---\n";
    $deleteRequest = Request::create("/api/v1/config-grado/{$gradoId}", 'DELETE');
    $deleteRequest->headers->set('Accept', 'application/json');
    
    Auth::login($user);
    $deleteResponse = $kernel->handle($deleteRequest);
    $deleteResponseData = json_decode($deleteResponse->getContent(), true);
    
    if ($deleteResponseData && $deleteResponseData['success']) {
        echo "✓ Grado eliminado exitosamente\n";
        
        // Verificar auditoría de eliminación
        sleep(1);
        $auditoriasEliminacion = DB::table('audits')
            ->where('model_type', 'App\\Models\\ConfigGrado')
            ->where('model_id', $gradoId)
            ->where('event', 'deleted')
            ->get();
        
        echo "✓ Auditorías de eliminación: " . $auditoriasEliminacion->count() . "\n";
    }

    // 8. Resumen final
    echo "\n=== RESUMEN FINAL ===\n";
    $totalAuditorias = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $gradoId)
        ->count();
    
    echo "✓ Total de auditorías registradas para el grado: {$totalAuditorias}\n";
    
    if ($auditoriasActualizacion->count() > 0) {
        echo "✅ SUCCESS: La auditoría de actualización SÍ funciona via HTTP\n";
    } else {
        echo "❌ PROBLEM: La auditoría de actualización NO funciona via HTTP\n";
    }

} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";