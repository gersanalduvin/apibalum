<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Producto;
use App\Models\InventarioKardex;
use App\Models\InventarioMovimiento;
use App\Services\MovimientoInventarioService;
use App\Repositories\MovimientoInventarioRepository;
use Illuminate\Foundation\Application;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRUEBA DE INTEGRACIÓN AUTOMÁTICA DEL KARDEX ===\n\n";

try {
    // Verificar productos disponibles
    $productos = Producto::count();
    echo "Productos en BD: $productos\n\n";
    
    if ($productos == 0) {
        echo "ERROR: No hay productos para probar\n";
        exit(1);
    }
    
    // Seleccionar el primer producto
    $producto = Producto::first();
    echo "Producto seleccionado: {$producto->nombre} (ID: {$producto->id})\n\n";
    
    // Contar registros de kardex antes
    $kardexAntes = InventarioKardex::where('producto_id', $producto->id)->count();
    echo "Registros de kardex ANTES: $kardexAntes\n\n";
    
    // Crear instancias necesarias con inyección de dependencias
    $movimientoRepo = new MovimientoInventarioRepository(new InventarioMovimiento());
    $movimientoService = new MovimientoInventarioService($movimientoRepo);
    
    // Crear un movimiento de entrada
    $datosMovimiento = [
        'producto_id' => $producto->id,
        'tipo_movimiento' => 'entrada',
        'cantidad' => 10.0000,
        'costo_unitario' => 25.50,
        'moneda' => false, // Córdoba
        'documento_tipo' => 'FACTURA',
        'documento_numero' => 'TEST-' . time(), // Número único basado en timestamp
        'observaciones' => 'Prueba de integración automática del kardex',
        'created_by' => 1 // ID del usuario de prueba
    ];
    
    echo "Creando movimiento de entrada...\n";
    $resultado = $movimientoService->createMovimiento($datosMovimiento);
    
    if ($resultado['success']) {
        echo "✓ Movimiento creado exitosamente\n";
        echo "  ID del movimiento: {$resultado['data']['id']}\n\n";
        
        // Verificar si se creó el kardex automáticamente
        $kardexDespues = InventarioKardex::where('producto_id', $producto->id)->count();
        echo "Registros de kardex DESPUÉS: $kardexDespues\n\n";
        
        if ($kardexDespues > $kardexAntes) {
            echo "✅ ÉXITO: Se creó automáticamente el registro de kardex\n";
            
            // Mostrar detalles del kardex creado
            $kardex = InventarioKardex::where('producto_id', $producto->id)
                                   ->where('movimiento_id', $resultado['data']['id'])
                                   ->first();
            
            if ($kardex) {
                echo "\nDetalles del kardex creado:\n";
                echo "- Fecha: {$kardex->fecha}\n";
                echo "- Tipo: {$kardex->tipo_movimiento}\n";
                echo "- Cantidad: {$kardex->cantidad}\n";
                echo "- Costo unitario: {$kardex->costo_unitario}\n";
                echo "- Stock anterior: {$kardex->stock_anterior}\n";
                echo "- Stock actual: {$kardex->stock_actual}\n";
                echo "- Saldo total: {$kardex->saldo_total}\n";
            }
        } else {
            echo "❌ ERROR: No se creó el registro de kardex automáticamente\n";
            exit(1);
        }
    } else {
        echo "❌ ERROR al crear el movimiento: {$resultado['message']}\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== PRUEBA COMPLETADA EXITOSAMENTE ===\n";