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
use Illuminate\Support\Facades\Validator;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA SIMPLE DE VALORES NULL DESDE FRONTEND ===\n\n";

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
    
    $service = app(ConfigPlanPagoDetalleService::class);
    $detalle = $service->create($createData);
    
    echo "✓ Registro creado - ID: {$detalle->id}\n";
    echo "  - asociar_mes en modelo: " . ($detalle->asociar_mes ?? 'NULL') . "\n";
    echo "  - orden_mes en modelo: {$detalle->orden_mes}\n";
    
    // 4. Probar prepareForValidation directamente con null
    echo "\n--- PRUEBA DE PREPAREFORVALIDATION CON NULL ---\n";
    
    $updateData = [
        'asociar_mes' => null
    ];
    
    echo "Datos originales: " . json_encode($updateData) . "\n";
    
    // Crear request con datos null
    $request = Request::create("/api/v1/config-plan-pago-detalle/{$detalle->id}", 'PUT', $updateData);
    $formRequest = ConfigPlanPagoDetalleRequest::createFrom($request);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app('redirect'));
    
    echo "Datos en FormRequest antes de prepareForValidation: " . json_encode($formRequest->all()) . "\n";
    
    // Ejecutar prepareForValidation manualmente
    $reflection = new ReflectionClass($formRequest);
    $method = $reflection->getMethod('prepareForValidation');
    $method->setAccessible(true);
    $method->invoke($formRequest);
    
    echo "Datos después de prepareForValidation: " . json_encode($formRequest->all()) . "\n";
    
    // Verificar si asociar_mes mantiene el valor null
    if ($formRequest->has('asociar_mes')) {
        echo "✓ asociar_mes está presente en el request\n";
        echo "  - Valor: " . ($formRequest->get('asociar_mes') ?? 'NULL') . "\n";
        echo "  - Tipo: " . gettype($formRequest->get('asociar_mes')) . "\n";
    } else {
        echo "❌ asociar_mes NO está presente en el request\n";
    }
    
    // 5. Probar con string vacío
    echo "\n--- PRUEBA DE PREPAREFORVALIDATION CON STRING VACÍO ---\n";
    
    $emptyData = [
        'asociar_mes' => ''
    ];
    
    echo "Datos originales: " . json_encode($emptyData) . "\n";
    
    $emptyRequest = Request::create("/api/v1/config-plan-pago-detalle/{$detalle->id}", 'PUT', $emptyData);
    $emptyFormRequest = ConfigPlanPagoDetalleRequest::createFrom($emptyRequest);
    $emptyFormRequest->setContainer(app());
    $emptyFormRequest->setRedirector(app('redirect'));
    
    echo "Datos en FormRequest antes de prepareForValidation: " . json_encode($emptyFormRequest->all()) . "\n";
    
    // Ejecutar prepareForValidation manualmente
    $emptyReflection = new ReflectionClass($emptyFormRequest);
    $emptyMethod = $emptyReflection->getMethod('prepareForValidation');
    $emptyMethod->setAccessible(true);
    $emptyMethod->invoke($emptyFormRequest);
    
    echo "Datos después de prepareForValidation: " . json_encode($emptyFormRequest->all()) . "\n";
    
    if ($emptyFormRequest->has('asociar_mes')) {
        echo "✓ asociar_mes está presente en el request\n";
        echo "  - Valor: '" . ($emptyFormRequest->get('asociar_mes') ?? 'NULL') . "'\n";
        echo "  - Tipo: " . gettype($emptyFormRequest->get('asociar_mes')) . "\n";
    } else {
        echo "❌ asociar_mes NO está presente en el request\n";
    }
    
    // 6. Probar validación manual
    echo "\n--- PRUEBA DE VALIDACIÓN MANUAL ---\n";
    
    $validator = Validator::make($formRequest->all(), [
        'asociar_mes' => 'nullable|in:enero,febrero,marzo,abril,mayo,junio,julio,agosto,septiembre,octubre,noviembre,diciembre'
    ]);
    
    if ($validator->passes()) {
        echo "✓ Validación pasó correctamente\n";
        $validatedData = $validator->validated();
        echo "Datos validados: " . json_encode($validatedData) . "\n";
        
        if (array_key_exists('asociar_mes', $validatedData)) {
            echo "✓ asociar_mes está presente en datos validados\n";
            echo "  - Valor: " . ($validatedData['asociar_mes'] ?? 'NULL') . "\n";
        } else {
            echo "❌ asociar_mes NO está presente en datos validados\n";
        }
    } else {
        echo "❌ Validación falló: " . json_encode($validator->errors()) . "\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA EXITOSAMENTE ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} finally {
    // Revertir transacción para no afectar la BD
    DB::rollBack();
    echo "\n✓ Transacción revertida - BD no modificada\n";
}