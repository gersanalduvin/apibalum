<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\ConfigPlanPagoDetalleRequest;
use App\Services\ConfigPlanPagoDetalleService;
use App\Models\User;
use App\Models\ConfigPlanPago;
use App\Models\ConfigPlanPagoDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE VALORES NULL DESDE FRONTEND ===\n\n";

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
    
    // 3. Crear un registro inicial con mes válido
    echo "\n--- CREACIÓN INICIAL ---\n";
    $createData = [
        'plan_pago_id' => $planPago->id,
        'codigo' => 'TEST001',
        'nombre' => 'Prueba Frontend Null',
        'importe' => 100.00,
        'moneda' => 1,
        'asociar_mes' => 'enero'
    ];
    
    // Simular request del frontend
    $request = Request::create('/api/v1/config-plan-pago-detalle', 'POST', $createData);
    $formRequest = ConfigPlanPagoDetalleRequest::createFrom($request);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));
    
    // Preparar validación (se ejecuta automáticamente en Laravel)
    echo "Datos después de prepareForValidation: " . json_encode($formRequest->all()) . "\n";
    
    // Validar
    $validator = app('validator')->make($formRequest->all(), $formRequest->rules());
    if ($validator->fails()) {
        echo "❌ Validación falló: " . json_encode($validator->errors()) . "\n";
        throw new Exception('Validación falló en creación');
    }
    
    $validatedData = $formRequest->validated();
    echo "Datos validados: " . json_encode($validatedData) . "\n";
    
    $service = new ConfigPlanPagoDetalleService();
    $detalle = $service->create($validatedData);
    
    echo "✓ Registro creado - ID: {$detalle->id}\n";
    echo "  - asociar_mes en modelo: " . ($detalle->asociar_mes ?? 'NULL') . "\n";
    echo "  - orden_mes en modelo: {$detalle->orden_mes}\n";
    
    // Verificar en BD
    $detalleDB = ConfigPlanPagoDetalle::find($detalle->id);
    echo "  - asociar_mes en BD: " . ($detalleDB->asociar_mes ?? 'NULL') . "\n";
    echo "  - orden_mes en BD: {$detalleDB->orden_mes}\n";
    
    // 4. Actualizar a null (simulando frontend)
    echo "\n--- ACTUALIZACIÓN A NULL (FRONTEND) ---\n";
    
    // Simular cómo el frontend envía null
    $updateData = [
        'asociar_mes' => null
    ];
    
    echo "Datos enviados desde frontend: " . json_encode($updateData) . "\n";
    
    // Simular request de actualización
    $updateRequest = Request::create("/api/v1/config-plan-pago-detalle/{$detalle->id}", 'PUT', $updateData);
    $updateFormRequest = ConfigPlanPagoDetalleRequest::createFrom($updateRequest);
    $updateFormRequest->setContainer(app());
    $updateFormRequest->setRedirector(app('redirect'));
    
    // Preparar validación (se ejecuta automáticamente en Laravel)
    echo "Datos después de prepareForValidation: " . json_encode($updateFormRequest->all()) . "\n";
    
    // Validar
    $updateValidator = app('validator')->make($updateFormRequest->all(), $updateFormRequest->rules());
    if ($updateValidator->fails()) {
        echo "❌ Validación falló: " . json_encode($updateValidator->errors()) . "\n";
        throw new Exception('Validación falló en actualización');
    }
    
    $updateValidatedData = $updateFormRequest->validated();
    echo "Datos validados para actualización: " . json_encode($updateValidatedData) . "\n";
    
    // Verificar si asociar_mes está presente en los datos validados
    if (array_key_exists('asociar_mes', $updateValidatedData)) {
        echo "✓ asociar_mes está presente en datos validados\n";
        echo "  - Valor: " . ($updateValidatedData['asociar_mes'] ?? 'NULL') . "\n";
    } else {
        echo "❌ asociar_mes NO está presente en datos validados\n";
    }
    
    // Actualizar usando el servicio
    $updatedDetalle = $service->update($detalle->id, $updateValidatedData);
    
    echo "✓ Registro actualizado\n";
    echo "  - asociar_mes en modelo: " . ($updatedDetalle->asociar_mes ?? 'NULL') . "\n";
    echo "  - orden_mes en modelo: {$updatedDetalle->orden_mes}\n";
    
    // Verificar en BD
    $detalleDB = ConfigPlanPagoDetalle::find($detalle->id);
    echo "  - asociar_mes en BD: " . ($detalleDB->asociar_mes ?? 'NULL') . "\n";
    echo "  - orden_mes en BD: {$detalleDB->orden_mes}\n";
    
    // 5. Probar con string vacío (otra forma común del frontend)
    echo "\n--- ACTUALIZACIÓN A STRING VACÍO (FRONTEND) ---\n";
    
    $emptyStringData = [
        'asociar_mes' => ''
    ];
    
    echo "Datos enviados desde frontend: " . json_encode($emptyStringData) . "\n";
    
    $emptyRequest = Request::create("/api/v1/config-plan-pago-detalle/{$detalle->id}", 'PUT', $emptyStringData);
    $emptyFormRequest = ConfigPlanPagoDetalleRequest::createFrom($emptyRequest);
    $emptyFormRequest->setContainer(app());
    $emptyFormRequest->setRedirector(app('redirect'));
    
    echo "Datos después de prepareForValidation: " . json_encode($emptyFormRequest->all()) . "\n";
    
    $emptyValidator = app('validator')->make($emptyFormRequest->all(), $emptyFormRequest->rules());
    if ($emptyValidator->fails()) {
        echo "❌ Validación falló: " . json_encode($emptyValidator->errors()) . "\n";
        throw new Exception('Validación falló con string vacío');
    }
    
    $emptyValidatedData = $emptyFormRequest->validated();
    echo "Datos validados para string vacío: " . json_encode($emptyValidatedData) . "\n";
    
    if (array_key_exists('asociar_mes', $emptyValidatedData)) {
        echo "✓ asociar_mes está presente en datos validados\n";
        echo "  - Valor: '" . ($emptyValidatedData['asociar_mes'] ?? 'NULL') . "'\n";
    } else {
        echo "❌ asociar_mes NO está presente en datos validados\n";
    }
    
    $updatedDetalle2 = $service->update($detalle->id, $emptyValidatedData);
    
    echo "✓ Registro actualizado con string vacío\n";
    echo "  - asociar_mes en modelo: '" . ($updatedDetalle2->asociar_mes ?? 'NULL') . "'\n";
    echo "  - orden_mes en modelo: {$updatedDetalle2->orden_mes}\n";
    
    // Verificar en BD
    $detalleDB = ConfigPlanPagoDetalle::find($detalle->id);
    echo "  - asociar_mes en BD: '" . ($detalleDB->asociar_mes ?? 'NULL') . "'\n";
    echo "  - orden_mes en BD: {$detalleDB->orden_mes}\n";
    
    echo "\n=== PRUEBA COMPLETADA EXITOSAMENTE ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} finally {
    // Revertir transacción para no afectar la BD
    DB::rollBack();
    echo "\n✓ Transacción revertida - BD no modificada\n";
}