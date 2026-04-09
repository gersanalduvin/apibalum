<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\ConfigPlanPagoDetalleController;
use App\Models\User;
use App\Models\ConfigPlanPago;
use App\Models\ConfigPlanPagoDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DIRECTA DEL CONTROLADOR ===\n\n";

try {
    DB::beginTransaction();
    
    // 1. Autenticar usuario
    $user = User::first();
    if (!$user) {
        throw new Exception('No hay usuarios en la base de datos');
    }
    Auth::login($user);
    echo "✓ Usuario autenticado: {$user->email}\n";
    
    // 2. Obtener un plan de pago existente
    $planPago = ConfigPlanPago::first();
    if (!$planPago) {
        throw new Exception('No hay planes de pago en la base de datos');
    }
    echo "✓ Plan de pago encontrado: {$planPago->nombre}\n";
    
    // 3. Crear un registro inicial con mes válido usando el controlador
    echo "\n--- CREACIÓN INICIAL VIA CONTROLADOR ---\n";
    $createData = [
        'plan_pago_id' => $planPago->id,
        'codigo' => 'TEST001',
        'nombre' => 'Prueba Controller Null',
        'importe' => 100.00,
        'moneda' => 1,
        'asociar_mes' => 'enero'
    ];
    
    $createRequest = Request::create('/api/v1/config-plan-pago-detalle', 'POST', $createData);
    $createRequest->headers->set('Accept', 'application/json');
    $createRequest->headers->set('Content-Type', 'application/json');
    
    $controller = app(ConfigPlanPagoDetalleController::class);
    $createResponse = $controller->store($createRequest);
    $createResponseData = json_decode($createResponse->getContent(), true);
    
    if ($createResponseData['success']) {
        $detalleId = $createResponseData['data']['id'];
        echo "✓ Registro creado via controlador - ID: {$detalleId}\n";
        echo "  - asociar_mes en respuesta: " . ($createResponseData['data']['asociar_mes'] ?? 'NULL') . "\n";
        echo "  - orden_mes en respuesta: {$createResponseData['data']['orden_mes']}\n";
        
        // Verificar en BD
        $detalleDB = ConfigPlanPagoDetalle::find($detalleId);
        echo "  - asociar_mes en BD: " . ($detalleDB->asociar_mes ?? 'NULL') . "\n";
        echo "  - orden_mes en BD: {$detalleDB->orden_mes}\n";
    } else {
        echo "❌ Error en creación: " . json_encode($createResponseData) . "\n";
        throw new Exception('Error en creación');
    }
    
    // 4. Actualizar a null usando el controlador
    echo "\n--- ACTUALIZACIÓN A NULL VIA CONTROLADOR ---\n";
    
    $updateData = [
        'asociar_mes' => null
    ];
    
    echo "Datos enviados al controlador: " . json_encode($updateData) . "\n";
    
    $updateRequest = Request::create("/api/v1/config-plan-pago-detalle/{$detalleId}", 'PUT', $updateData);
    $updateRequest->headers->set('Accept', 'application/json');
    $updateRequest->headers->set('Content-Type', 'application/json');
    
    $updateResponse = $controller->update($updateRequest, $detalleId);
    $updateResponseData = json_decode($updateResponse->getContent(), true);
    
    echo "Respuesta del controlador: " . json_encode($updateResponseData) . "\n";
    
    if ($updateResponseData['success']) {
        echo "✓ Actualización exitosa via controlador\n";
        echo "  - asociar_mes en respuesta: " . ($updateResponseData['data']['asociar_mes'] ?? 'NULL') . "\n";
        echo "  - orden_mes en respuesta: {$updateResponseData['data']['orden_mes']}\n";
        
        // Verificar en BD
        $detalleDB = ConfigPlanPagoDetalle::find($detalleId);
        echo "  - asociar_mes en BD: " . ($detalleDB->asociar_mes ?? 'NULL') . "\n";
        echo "  - orden_mes en BD: {$detalleDB->orden_mes}\n";
        
        if ($detalleDB->asociar_mes === null) {
            echo "✅ SUCCESS: asociar_mes se guardó correctamente como NULL\n";
        } else {
            echo "❌ PROBLEM: asociar_mes NO se guardó como NULL, valor actual: '{$detalleDB->asociar_mes}'\n";
        }
    } else {
        echo "❌ Error en actualización: " . json_encode($updateResponseData) . "\n";
    }
    
    // 5. Probar con string vacío
    echo "\n--- ACTUALIZACIÓN A STRING VACÍO VIA CONTROLADOR ---\n";
    
    $emptyData = [
        'asociar_mes' => ''
    ];
    
    echo "Datos enviados al controlador: " . json_encode($emptyData) . "\n";
    
    $emptyRequest = Request::create("/api/v1/config-plan-pago-detalle/{$detalleId}", 'PUT', $emptyData);
    $emptyRequest->headers->set('Accept', 'application/json');
    $emptyRequest->headers->set('Content-Type', 'application/json');
    
    $emptyResponse = $controller->update($emptyRequest, $detalleId);
    $emptyResponseData = json_decode($emptyResponse->getContent(), true);
    
    echo "Respuesta del controlador: " . json_encode($emptyResponseData) . "\n";
    
    if ($emptyResponseData['success']) {
        echo "✓ Actualización con string vacío exitosa via controlador\n";
        echo "  - asociar_mes en respuesta: '" . ($emptyResponseData['data']['asociar_mes'] ?? 'NULL') . "'\n";
        echo "  - orden_mes en respuesta: {$emptyResponseData['data']['orden_mes']}\n";
        
        // Verificar en BD
        $detalleDB = ConfigPlanPagoDetalle::find($detalleId);
        echo "  - asociar_mes en BD: '" . ($detalleDB->asociar_mes ?? 'NULL') . "'\n";
        echo "  - orden_mes en BD: {$detalleDB->orden_mes}\n";
        
        if ($detalleDB->asociar_mes === null || $detalleDB->asociar_mes === '') {
            echo "✅ SUCCESS: asociar_mes se manejó correctamente\n";
        } else {
            echo "❌ PROBLEM: asociar_mes tiene un valor inesperado: '{$detalleDB->asociar_mes}'\n";
        }
    } else {
        echo "❌ Error en actualización con string vacío: " . json_encode($emptyResponseData) . "\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} finally {
    // Revertir transacción para no afectar la BD
    DB::rollBack();
    echo "\n✓ Transacción revertida - BD no modificada\n";
}