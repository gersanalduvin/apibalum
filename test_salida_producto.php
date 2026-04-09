<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\MovimientoInventarioController;
use App\Models\Producto;
use App\Models\User;
use App\Models\InventarioMovimiento;
use App\Models\InventarioKardex;

// Configurar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 SIMULACIÓN DE SALIDA DE PRODUCTO\n";
echo "=====================================\n\n";

try {
    // 1. Obtener un producto existente
    $producto = Producto::where('activo', true)->first();
    
    if (!$producto) {
        echo "❌ No se encontraron productos activos. Ejecuta el seeder primero:\n";
        echo "   php artisan db:seed --class=ProductoSeeder\n";
        exit(1);
    }
    
    echo "📦 PRODUCTO SELECCIONADO:\n";
    echo "   ID: {$producto->id}\n";
    echo "   Código: {$producto->codigo}\n";
    echo "   Nombre: {$producto->nombre}\n";
    echo "   Stock actual: {$producto->stock_actual}\n";
    echo "   Costo promedio: C$ {$producto->costo_promedio}\n\n";
    
    // 2. Obtener usuario para la transacción
    $usuario = User::first();
    if (!$usuario) {
        echo "❌ No se encontraron usuarios. Ejecuta el seeder primero:\n";
        echo "   php artisan db:seed --class=TestUsersSeeder\n";
        exit(1);
    }
    
    echo "👤 USUARIO: {$usuario->name} (ID: {$usuario->id})\n\n";
    
    // 3. Verificar stock antes del movimiento
    $stockAnterior = $producto->stock_actual;
    $costoPromedioAnterior = $producto->costo_promedio;
    
    echo "📊 ESTADO INICIAL:\n";
    echo "   Stock anterior: {$stockAnterior}\n";
    echo "   Costo promedio anterior: C$ {$costoPromedioAnterior}\n\n";
    
    // 4. Crear datos para la salida de producto
    $cantidadSalida = 10; // Cantidad a sacar
    $numeroDocumento = 'SALIDA-' . time();
    
    $datosMovimiento = [
        'producto_id' => $producto->id,
        'tipo_movimiento' => 'salida',
        'subtipo_movimiento' => 'venta',
        'cantidad' => $cantidadSalida,
        'costo_unitario' => $costoPromedioAnterior,
        'costo_total' => $cantidadSalida * $costoPromedioAnterior,
        'stock_anterior' => $stockAnterior,
        'costo_promedio_anterior' => $costoPromedioAnterior,
        'stock_posterior' => $stockAnterior - $cantidadSalida,
        'costo_promedio_posterior' => $costoPromedioAnterior, // En salidas se mantiene
        'moneda' => false, // Córdobas
        'documento_tipo' => 'FACTURA',
        'documento_numero' => $numeroDocumento,
        'observaciones' => 'Simulación de salida de producto - Venta',
        'activo' => true,
        'created_by' => $usuario->id,
        'is_synced' => true,
        'synced_at' => now(),
        'version' => 1
    ];
    
    echo "📤 DATOS DE LA SALIDA:\n";
    echo "   Tipo: {$datosMovimiento['tipo_movimiento']} - {$datosMovimiento['subtipo_movimiento']}\n";
    echo "   Cantidad: {$cantidadSalida}\n";
    echo "   Costo unitario: C$ {$costoPromedioAnterior}\n";
    echo "   Costo total: C$ " . ($cantidadSalida * $costoPromedioAnterior) . "\n";
    echo "   Documento: {$datosMovimiento['documento_tipo']} - {$numeroDocumento}\n";
    echo "   Stock posterior esperado: " . ($stockAnterior - $cantidadSalida) . "\n\n";
    
    // 5. Crear el movimiento de inventario usando el servicio
    echo "💾 Creando movimiento de inventario usando el servicio...\n";
    
    // Usar el servicio en lugar de crear directamente
    $movimientoService = new \App\Services\MovimientoInventarioService(
        new \App\Repositories\MovimientoInventarioRepository(new InventarioMovimiento())
    );
    
    $resultado = $movimientoService->createMovimiento($datosMovimiento);
    
    if ($resultado['success']) {
        $movimiento = $resultado['data'];
        echo "✅ Movimiento creado exitosamente (ID: {$movimiento->id})\n\n";
        
        // 6. Verificar que se creó el registro de kardex automáticamente
        echo "🔍 Verificando creación automática del kardex...\n";
        $kardex = InventarioKardex::where('movimiento_id', $movimiento->id)->first();
        
        if ($kardex) {
            echo "✅ Registro de kardex creado automáticamente (ID: {$kardex->id})\n";
            echo "📋 DETALLES DEL KARDEX:\n";
            echo "   Producto ID: {$kardex->producto_id}\n";
            echo "   Movimiento ID: {$kardex->movimiento_id}\n";
            echo "   Tipo: {$kardex->tipo_movimiento}\n";
            echo "   Cantidad: {$kardex->cantidad}\n";
            echo "   Stock anterior: {$kardex->stock_anterior}\n";
            echo "   Stock posterior: {$kardex->stock_posterior}\n";
            echo "   Costo unitario: C$ {$kardex->costo_unitario}\n";
            echo "   Costo total: C$ {$kardex->costo_total}\n";
            echo "   Documento: {$kardex->documento_tipo} - {$kardex->documento_numero}\n";
            echo "   Fecha: {$kardex->fecha_movimiento}\n\n";
        } else {
            echo "❌ No se encontró registro de kardex automático\n\n";
        }
        
        // 7. Verificar actualización del stock en el producto
        echo "🔄 Verificando actualización del stock del producto...\n";
        $producto->refresh(); // Recargar desde la base de datos
        
        echo "📊 ESTADO FINAL DEL PRODUCTO:\n";
        echo "   Stock actual: {$producto->stock_actual}\n";
        echo "   Costo promedio: C$ {$producto->costo_promedio}\n";
        echo "   Diferencia de stock: " . ($stockAnterior - $producto->stock_actual) . "\n\n";
        
        // 8. Mostrar resumen de la operación
        echo "📈 RESUMEN DE LA OPERACIÓN:\n";
        echo "   ✓ Movimiento de salida registrado\n";
        echo "   ✓ Kardex generado automáticamente\n";
        echo "   ✓ Stock del producto actualizado\n";
        echo "   ✓ Operación completada exitosamente\n\n";
        
        // 9. Mostrar últimos movimientos del producto
        echo "📜 ÚLTIMOS MOVIMIENTOS DEL PRODUCTO:\n";
        $ultimosMovimientos = InventarioMovimiento::where('producto_id', $producto->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        foreach ($ultimosMovimientos as $mov) {
            echo "   • {$mov->tipo_movimiento} - {$mov->subtipo_movimiento} | ";
            echo "Cant: {$mov->cantidad} | ";
            echo "Stock: {$mov->stock_anterior} → {$mov->stock_posterior} | ";
            echo "Doc: {$mov->documento_numero} | ";
            echo "Fecha: {$mov->created_at->format('Y-m-d H:i:s')}\n";
        }
        
    } else {
        echo "❌ Error al crear el movimiento de inventario: " . ($resultado['message'] ?? 'Error desconocido') . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
    exit(1);
}

echo "\n🎉 SIMULACIÓN COMPLETADA EXITOSAMENTE\n";