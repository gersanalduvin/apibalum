<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\ConfigPlanPago;
use App\Models\ConfigPlanPagoDetalle;
use Illuminate\Support\Facades\DB;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA HTTP REAL DE LA API ===\n\n";

try {
    DB::beginTransaction();
    
    // 1. Obtener datos necesarios
    $user = User::first();
    if (!$user) {
        throw new Exception('No hay usuarios en la base de datos');
    }
    echo "✓ Usuario encontrado: {$user->email}\n";
    
    $planPago = ConfigPlanPago::first();
    if (!$planPago) {
        throw new Exception('No hay planes de pago en la base de datos');
    }
    echo "✓ Plan de pago encontrado: {$planPago->nombre}\n";
    
    // 2. Obtener token de autenticación (simulando login)
    $loginData = [
        'email' => 'superadmin@test.com',
        'password' => 'password' // Contraseña del seeder
    ];
    
    $loginCurl = curl_init();
    curl_setopt_array($loginCurl, [
        CURLOPT_URL => 'http://localhost:8081/api/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($loginData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    
    $loginResponse = curl_exec($loginCurl);
    $loginHttpCode = curl_getinfo($loginCurl, CURLINFO_HTTP_CODE);
    curl_close($loginCurl);
    
    if ($loginHttpCode !== 200) {
        echo "❌ Error en login HTTP {$loginHttpCode}: {$loginResponse}\n";
        throw new Exception('Error en autenticación');
    }
    
    $loginData = json_decode($loginResponse, true);
    if (!$loginData || !isset($loginData['success']) || !$loginData['success']) {
        echo "❌ Error en login: " . ($loginResponse ?: 'Sin respuesta') . "\n";
        throw new Exception('Error en autenticación');
    }
    
    if (!isset($loginData['token'])) {
        echo "❌ Token no encontrado en respuesta: " . json_encode($loginData) . "\n";
        throw new Exception('Token no encontrado');
    }
    
    $token = $loginData['token'];
    echo "✓ Token obtenido exitosamente\n";
    
    // 3. Crear registro inicial con mes válido
    echo "\n--- CREACIÓN INICIAL VIA HTTP ---\n";
    $createData = [
        'plan_pago_id' => $planPago->id,
        'codigo' => 'TEST001',
        'nombre' => 'Prueba HTTP Null',
        'importe' => 100.00,
        'moneda' => 1,
        'asociar_mes' => 'enero'
    ];
    
    echo "Datos enviados: " . json_encode($createData) . "\n";
    
    $createCurl = curl_init();
    curl_setopt_array($createCurl, [
        CURLOPT_URL => 'http://localhost:8081/api/v1/config-plan-pago-detalle',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($createData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]
    ]);
    
    $createResponse = curl_exec($createCurl);
    $createHttpCode = curl_getinfo($createCurl, CURLINFO_HTTP_CODE);
    curl_close($createCurl);
    
    echo "HTTP Code: {$createHttpCode}\n";
    echo "Respuesta: {$createResponse}\n";
    
    if ($createHttpCode !== 201) {
        throw new Exception("Error en creación HTTP {$createHttpCode}");
    }
    
    $createResponseData = json_decode($createResponse, true);
    if (!$createResponseData['success']) {
        throw new Exception('Error en creación: ' . json_encode($createResponseData));
    }
    
    $detalleId = $createResponseData['data']['id'];
    echo "✓ Registro creado - ID: {$detalleId}\n";
    echo "  - asociar_mes en respuesta: " . ($createResponseData['data']['asociar_mes'] ?? 'NULL') . "\n";
    echo "  - orden_mes en respuesta: {$createResponseData['data']['orden_mes']}\n";
    
    // 4. Actualizar a null via HTTP
    echo "\n--- ACTUALIZACIÓN A NULL VIA HTTP ---\n";
    
    $updateData = [
        'asociar_mes' => null
    ];
    
    echo "Datos enviados: " . json_encode($updateData) . "\n";
    
    $updateCurl = curl_init();
    curl_setopt_array($updateCurl, [
        CURLOPT_URL => "http://localhost:8081/api/v1/config-plan-pago-detalle/{$detalleId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($updateData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]
    ]);
    
    $updateResponse = curl_exec($updateCurl);
    $updateHttpCode = curl_getinfo($updateCurl, CURLINFO_HTTP_CODE);
    curl_close($updateCurl);
    
    echo "HTTP Code: {$updateHttpCode}\n";
    echo "Respuesta: {$updateResponse}\n";
    
    if ($updateHttpCode === 200) {
        $updateResponseData = json_decode($updateResponse, true);
        if ($updateResponseData['success']) {
            echo "✓ Actualización exitosa\n";
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
            echo "❌ Error en respuesta: " . json_encode($updateResponseData) . "\n";
        }
    } else {
        echo "❌ Error HTTP en actualización: {$updateHttpCode}\n";
    }
    
    // 5. Probar con string vacío
    echo "\n--- ACTUALIZACIÓN A STRING VACÍO VIA HTTP ---\n";
    
    $emptyData = [
        'asociar_mes' => ''
    ];
    
    echo "Datos enviados: " . json_encode($emptyData) . "\n";
    
    $emptyCurl = curl_init();
    curl_setopt_array($emptyCurl, [
        CURLOPT_URL => "http://localhost:8081/api/v1/config-plan-pago-detalle/{$detalleId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($emptyData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ]
    ]);
    
    $emptyResponse = curl_exec($emptyCurl);
    $emptyHttpCode = curl_getinfo($emptyCurl, CURLINFO_HTTP_CODE);
    curl_close($emptyCurl);
    
    echo "HTTP Code: {$emptyHttpCode}\n";
    echo "Respuesta: {$emptyResponse}\n";
    
    if ($emptyHttpCode === 200) {
        $emptyResponseData = json_decode($emptyResponse, true);
        if ($emptyResponseData['success']) {
            echo "✓ Actualización con string vacío exitosa\n";
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
            echo "❌ Error en respuesta: " . json_encode($emptyResponseData) . "\n";
        }
    } else {
        echo "❌ Error HTTP en actualización con string vacío: {$emptyHttpCode}\n";
    }
    
    echo "\n=== PRUEBA HTTP COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} finally {
    // Revertir transacción para no afectar la BD
    DB::rollBack();
    echo "\n✓ Transacción revertida - BD no modificada\n";
}