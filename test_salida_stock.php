<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MovimientoInventarioService;
use App\Repositories\ProductoRepository;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Iniciando prueba de movimiento de SALIDA ===\n";
    
    // Buscar un producto con stock disponible
    $producto = DB::table('inventario_producto')
        ->where('activo', true)
        ->where('stock_actual', '>', 0)
        ->first();
    
    if (!$producto) {
        echo "No se encontró ningún producto con stock disponible\n";
        exit(1);
    }
    
    echo "Producto encontrado: ID {$producto->id}, Código: {$producto->codigo}, Nombre: {$producto->nombre}\n";
    echo "Stock actual: {$producto->stock_actual}\n";
    
    // Crear instancias de los servicios
    $movimientoService = app(MovimientoInventarioService::class);
    $productoRepository = app(ProductoRepository::class);
    
    // Datos del movimiento de salida
    $cantidadSalida = 5; // Cantidad menor al stock disponible
    $datosMovimiento = [
        'producto_id' => $producto->id,
        'tipo_movimiento' => 'salida',
        'cantidad' => $cantidadSalida,
        'costo_unitario' => 0, // Para salidas no se requiere costo
        'observaciones' => 'Movimiento de prueba - salida de inventario',
        'documento_tipo' => 'VENTA',
        'documento_numero' => 'SALIDA-' . time(), // Número único usando timestamp
        'documento_fecha' => now()->format('Y-m-d'),
        'moneda' => false, // Córdoba
        'activo' => true,
        'created_by' => 1 // Asumiendo que existe un usuario con ID 1
    ];
    
    echo "\n=== Creando movimiento de salida ===\n";
    echo "Cantidad a retirar: {$datosMovimiento['cantidad']}\n";
    
    // Crear el movimiento
    $resultado = $movimientoService->createMovimiento($datosMovimiento);
    
    echo "Movimiento creado exitosamente con ID: {$resultado['data']->id}\n";
    
    // Verificar el stock actualizado
    $productoActualizado = $productoRepository->find($producto->id);
    echo "\n=== Verificación de stock ===\n";
    echo "Stock anterior: {$producto->stock_actual}\n";
    echo "Stock actual: {$productoActualizado->stock_actual}\n";
    echo "Diferencia: " . ($productoActualizado->stock_actual - $producto->stock_actual) . "\n";
    
    $stockEsperado = $producto->stock_actual - $datosMovimiento['cantidad'];
    if ($productoActualizado->stock_actual == $stockEsperado) {
        echo "✅ El stock se actualizó correctamente (se decrementó)\n";
    } else {
        echo "❌ Error: El stock no se actualizó como se esperaba\n";
        echo "Stock esperado: {$stockEsperado}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}