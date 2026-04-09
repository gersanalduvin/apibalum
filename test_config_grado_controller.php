<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\ConfigGradoController;
use App\Http\Requests\Api\V1\ConfigGradoRequest;
use App\Models\ConfigGrado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE AUDITORÍA CONFIGGRADO VIA CONTROLADOR ===\n\n";

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
    
    // Autenticar el usuario
    Auth::login($user);
    echo "✓ Usuario autenticado: {$user->email}\n";

    // 2. Crear un grado inicial usando el controlador
    echo "\n--- CREACIÓN VIA CONTROLADOR ---\n";
    
    // Simular request de creación
    $createData = [
        'nombre' => 'Grado Prueba Controller',
        'abreviatura' => 'GPC',
        'orden' => 999
    ];
    
    $createRequest = new Request($createData);
    $createRequest->setMethod('POST');
    $createRequest->headers->set('Accept', 'application/json');
    $createRequest->headers->set('Content-Type', 'application/json');
    
    // Crear una instancia del controlador
    $controller = app(ConfigGradoController::class);
    
    // Crear el FormRequest manualmente
    $formRequest = new ConfigGradoRequest();
    $formRequest->replace($createData);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));
    
    // Llamar al método store del controlador
    $createResponse = $controller->store($formRequest);
    $createResponseData = json_decode($createResponse->getContent(), true);
    
    if (!$createResponseData['success']) {
        throw new Exception('Error al crear grado: ' . $createResponseData['message']);
    }
    
    $gradoId = $createResponseData['data']['id'];
    echo "✓ Grado creado via controlador - ID: {$gradoId}\n";
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
    
    // 4. Actualizar el grado usando el controlador
    echo "\n--- ACTUALIZACIÓN VIA CONTROLADOR ---\n";
    
    $updateData = [
        'nombre' => 'Grado Actualizado Controller',
        'abreviatura' => 'GAC',
        'orden' => 888
    ];
    
    $updateRequest = new Request($updateData);
    $updateRequest->setMethod('PUT');
    $updateRequest->headers->set('Accept', 'application/json');
    $updateRequest->headers->set('Content-Type', 'application/json');
    
    // Crear el FormRequest para actualización
    $updateFormRequest = new ConfigGradoRequest();
    $updateFormRequest->replace($updateData);
    $updateFormRequest->setContainer(app());
    $updateFormRequest->setRedirector(app('redirect'));
    
    // Llamar al método update del controlador
    $updateResponse = $controller->update($updateFormRequest, $gradoId);
    $updateResponseData = json_decode($updateResponse->getContent(), true);
    
    if (!$updateResponseData['success']) {
        throw new Exception('Error al actualizar grado: ' . $updateResponseData['message']);
    }
    
    echo "✓ Grado actualizado via controlador\n";
    echo "  - Nombre: {$updateResponseData['data']['nombre']}\n";
    echo "  - Abreviatura: {$updateResponseData['data']['abreviatura']}\n";
    echo "  - Orden: {$updateResponseData['data']['orden']}\n";

    // 5. Verificar auditoría de actualización
    echo "\n--- VERIFICACIÓN DE AUDITORÍA DE ACTUALIZACIÓN ---\n";
    
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
    }

    // 6. Verificar el estado actual del grado en la BD
    echo "\n--- VERIFICACIÓN EN BASE DE DATOS ---\n";
    $gradoActual = ConfigGrado::find($gradoId);
    if ($gradoActual) {
        echo "✓ Grado encontrado en BD:\n";
        echo "  - ID: {$gradoActual->id}\n";
        echo "  - Nombre: {$gradoActual->nombre}\n";
        echo "  - Abreviatura: {$gradoActual->abreviatura}\n";
        echo "  - Orden: {$gradoActual->orden}\n";
        echo "  - Updated by: {$gradoActual->updated_by}\n";
        echo "  - Updated at: {$gradoActual->updated_at}\n";
        echo "  - Version: {$gradoActual->version}\n";
    }

    // 7. Mostrar todas las auditorías del grado
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

    // 8. Limpiar - eliminar el grado de prueba
    echo "\n--- LIMPIEZA ---\n";
    $deleteResponse = $controller->destroy($gradoId);
    $deleteResponseData = json_decode($deleteResponse->getContent(), true);
    
    if ($deleteResponseData['success']) {
        echo "✓ Grado eliminado exitosamente\n";
        
        // Verificar auditoría de eliminación
        $auditoriasEliminacion = DB::table('audits')
            ->where('model_type', 'App\\Models\\ConfigGrado')
            ->where('model_id', $gradoId)
            ->where('event', 'deleted')
            ->get();
        
        echo "✓ Auditorías de eliminación: " . $auditoriasEliminacion->count() . "\n";
    }

    // 9. Resumen final
    echo "\n=== RESUMEN FINAL ===\n";
    $totalAuditorias = DB::table('audits')
        ->where('model_type', 'App\\Models\\ConfigGrado')
        ->where('model_id', $gradoId)
        ->count();
    
    echo "✓ Total de auditorías registradas para el grado: {$totalAuditorias}\n";
    
    if ($auditoriasActualizacion->count() > 0) {
        echo "✅ SUCCESS: La auditoría de actualización SÍ funciona via controlador\n";
    } else {
        echo "❌ PROBLEM: La auditoría de actualización NO funciona via controlador\n";
    }

} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";