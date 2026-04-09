<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\MovimientoInventarioService;
use App\Repositories\ProductoRepository;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Iniciando prueba de movimiento de inventario ===\n";
    
    // Buscar cualquier producto activo
    $producto = DB::table('inventario_producto')
        ->where('activo', true)
        ->first();
    
    if (!$producto) {
        echo "No se encontró ningún producto activo\n";
        exit(1);
    }
    
    echo "Producto encontrado: ID {$producto->id}, Código: {$producto->codigo}, Nombre: {$producto->nombre}\n";
    echo "Stock actual: {$producto->stock_actual}\n";
    
    // Crear instancias de los servicios
    $movimientoService = app(MovimientoInventarioService::class);
    $productoRepository = app(ProductoRepository::class);
    
    // Datos del movimiento de entrada
    $datosMovimiento = [
        'producto_id' => $producto->id,
        'tipo_movimiento' => 'entrada',
        'cantidad' => 10,
        'costo_unitario' => 15.50,
        'observaciones' => 'Movimiento de prueba - entrada de inventario',
        'documento_tipo' => 'FACTURA',
        'documento_numero' => 'TEST-' . time(), // Número único usando timestamp
        'documento_fecha' => now()->format('Y-m-d'),
        'moneda' => false, // Córdoba
        'activo' => true,
        'created_by' => 1 // Asumiendo que existe un usuario con ID 1
    ];
    
    echo "\n=== Creando movimiento de entrada ===\n";
    echo "Cantidad a ingresar: {$datosMovimiento['cantidad']}\n";
    echo "Costo unitario: {$datosMovimiento['costo_unitario']}\n";
    
    // Crear el movimiento
    $resultado = $movimientoService->createMovimiento($datosMovimiento);
    
    echo "Movimiento creado exitosamente con ID: {$resultado['data']->id}\n";
    
    // Verificar el stock actualizado
    $productoActualizado = $productoRepository->find($producto->id);
    echo "\n=== Verificación de stock ===\n";
    echo "Stock anterior: {$producto->stock_actual}\n";
    echo "Stock actual: {$productoActualizado->stock_actual}\n";
    echo "Diferencia: " . ($productoActualizado->stock_actual - $producto->stock_actual) . "\n";
    
    if ($productoActualizado->stock_actual == ($producto->stock_actual + $datosMovimiento['cantidad'])) {
        echo "✅ El stock se actualizó correctamente\n";
    } else {
        echo "❌ Error: El stock no se actualizó como se esperaba\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}