<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Producto;
use App\Services\MovimientoInventarioService;
use Illuminate\Support\Facades\Auth;

// Simular usuario autenticado
Auth::loginUsingId(1);

try {
    // Obtener un producto existente
    $producto = Producto::first();
    
    if (!$producto) {
        echo "❌ No hay productos disponibles para la prueba\n";
        exit;
    }
    
    echo "📦 Producto seleccionado: {$producto->nombre} (ID: {$producto->id})\n";
    echo "📊 Stock actual: {$producto->stock_actual}\n\n";
    
    // Crear instancia del servicio
    $movimientoService = app(MovimientoInventarioService::class);
    
    // Datos del movimiento con documento_tipo y documento_numero
    $datosMovimiento = [
        'producto_id' => $producto->id,
        'tipo_movimiento' => 'entrada',
        'cantidad' => 10,
        'costo_unitario' => 25.50,
        'documento_tipo' => 'FACTURA',
        'documento_numero' => 'FAC-TEST-' . time(), // Usar timestamp para evitar duplicados
        'documento_fecha' => now()->format('Y-m-d'),
        'observaciones' => 'Prueba de campos documento_tipo y documento_numero',
        'motivo_ajuste' => null
    ];
    
    echo "🔄 Creando movimiento con los siguientes datos:\n";
    echo "   - Tipo: {$datosMovimiento['tipo_movimiento']}\n";
    echo "   - Cantidad: {$datosMovimiento['cantidad']}\n";
    echo "   - Documento Tipo: {$datosMovimiento['documento_tipo']}\n";
    echo "   - Documento Número: {$datosMovimiento['documento_numero']}\n";
    echo "   - Fecha: {$datosMovimiento['documento_fecha']}\n\n";
    
    // Crear el movimiento
    $resultado = $movimientoService->createMovimiento($datosMovimiento);
    
    if ($resultado['success']) {
        $movimiento = $resultado['data'];
        
        echo "✅ Movimiento creado exitosamente!\n";
        echo "   - ID: {$movimiento->id}\n";
        echo "   - UUID: {$movimiento->uuid}\n";
        echo "   - Documento Tipo guardado: '{$movimiento->documento_tipo}'\n";
        echo "   - Documento Número guardado: '{$movimiento->documento_numero}'\n";
        echo "   - Documento Fecha: {$movimiento->documento_fecha}\n";
        echo "   - Stock anterior: {$movimiento->stock_anterior}\n";
        echo "   - Stock posterior: {$movimiento->stock_posterior}\n\n";
        
        // Verificar que los campos se guardaron correctamente
        if ($movimiento->documento_tipo === $datosMovimiento['documento_tipo']) {
            echo "✅ Campo 'documento_tipo' se guardó correctamente\n";
        } else {
            echo "❌ Campo 'documento_tipo' NO se guardó correctamente\n";
            echo "   Esperado: '{$datosMovimiento['documento_tipo']}'\n";
            echo "   Guardado: '{$movimiento->documento_tipo}'\n";
        }
        
        if ($movimiento->documento_numero === $datosMovimiento['documento_numero']) {
            echo "✅ Campo 'documento_numero' se guardó correctamente\n";
        } else {
            echo "❌ Campo 'documento_numero' NO se guardó correctamente\n";
            echo "   Esperado: '{$datosMovimiento['documento_numero']}'\n";
            echo "   Guardado: '{$movimiento->documento_numero}'\n";
        }
        
        // Verificar el producto actualizado
        $producto->refresh();
        echo "\n📊 Stock actualizado del producto: {$producto->stock_actual}\n";
        
    } else {
        echo "❌ Error al crear movimiento: {$resultado['message']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
}