<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;

class InventarioMovimientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener productos y usuarios para las relaciones
        $productos = Producto::all();
        $usuarios = User::all();
        
        if ($productos->isEmpty() || $usuarios->isEmpty()) {
            $this->command->warn('No hay productos o usuarios disponibles. Ejecuta primero ProductoSeeder y UserSeeder.');
            return;
        }

        $usuario = $usuarios->first();
        $fechaBase = Carbon::now()->subDays(30);

        // Movimientos de ejemplo para cada producto
        foreach ($productos->take(5) as $index => $producto) {
            
            // 1. Ajuste inicial de inventario (entrada)
            InventarioMovimiento::create([
                'producto_id' => $producto->id,
                'tipo_movimiento' => 'ajuste_positivo',
                'subtipo_movimiento' => 'inventario_inicial',
                'cantidad' => 100 + ($index * 50), // Stock inicial variable
                'costo_unitario' => 15.50 + ($index * 5), // Costo variable
                'costo_total' => (100 + ($index * 50)) * (15.50 + ($index * 5)),
                'stock_anterior' => 0,
                'costo_promedio_anterior' => 0,
                'stock_posterior' => 100 + ($index * 50),
                'costo_promedio_posterior' => 15.50 + ($index * 5),
                'moneda' => false, // Córdobas
                'documento_tipo' => 'AJUSTE',
                'documento_numero' => 'AJ-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'documento_fecha' => $fechaBase->copy()->addDays($index),
                'observaciones' => 'Inventario inicial del producto ' . $producto->nombre,
                'ubicacion' => 'BODEGA-A',
                'activo' => true,
                'es_reversible' => false,
                'created_by' => $usuario->id,
                'is_synced' => true,
                'synced_at' => now(),
                'version' => 1
            ]);

            // 2. Compra de mercadería (entrada)
            InventarioMovimiento::create([
                'producto_id' => $producto->id,
                'tipo_movimiento' => 'entrada',
                'subtipo_movimiento' => 'compra',
                'cantidad' => 50,
                'costo_unitario' => 16.00 + ($index * 2),
                'costo_total' => 50 * (16.00 + ($index * 2)),
                'stock_anterior' => 100 + ($index * 50),
                'costo_promedio_anterior' => 15.50 + ($index * 5),
                'stock_posterior' => 150 + ($index * 50),
                'costo_promedio_posterior' => ((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50)),
                'moneda' => false,
                'documento_tipo' => 'FACTURA',
                'documento_numero' => 'FC-' . str_pad(1000 + $index, 4, '0', STR_PAD_LEFT),
                'documento_fecha' => $fechaBase->copy()->addDays($index + 5),
                'proveedor_id' => $usuarios->skip(1)->first()?->id,
                'observaciones' => 'Compra de mercadería - Proveedor ABC',
                'ubicacion' => 'BODEGA-A',
                'lote' => 'LOTE-' . date('Y') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'fecha_vencimiento' => $fechaBase->copy()->addYear(),
                'activo' => true,
                'es_reversible' => true,
                'created_by' => $usuario->id,
                'is_synced' => true,
                'synced_at' => now(),
                'version' => 1
            ]);

            // 3. Venta de producto (salida)
            InventarioMovimiento::create([
                'producto_id' => $producto->id,
                'tipo_movimiento' => 'salida',
                'subtipo_movimiento' => 'venta',
                'cantidad' => 25,
                'costo_unitario' => ((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50)), // Costo promedio
                'costo_total' => 25 * (((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50))),
                'stock_anterior' => 150 + ($index * 50),
                'costo_promedio_anterior' => ((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50)),
                'stock_posterior' => 125 + ($index * 50),
                'costo_promedio_posterior' => ((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50)),
                'moneda' => false,
                'documento_tipo' => 'FACTURA',
                'documento_numero' => 'FV-' . str_pad(2000 + $index, 4, '0', STR_PAD_LEFT),
                'documento_fecha' => $fechaBase->copy()->addDays($index + 10),
                'cliente_id' => $usuarios->skip(2)->first()?->id,
                'observaciones' => 'Venta a cliente - Factura ' . (2000 + $index),
                'ubicacion' => 'BODEGA-A',
                'lote' => 'LOTE-' . date('Y') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'activo' => true,
                'es_reversible' => true,
                'created_by' => $usuario->id,
                'is_synced' => true,
                'synced_at' => now(),
                'version' => 1
            ]);

            // 4. Ajuste negativo por daño
            if ($index < 2) { // Solo para los primeros 2 productos
                InventarioMovimiento::create([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'ajuste_negativo',
                    'subtipo_movimiento' => 'dano_deterioro',
                    'cantidad' => 5,
                    'costo_unitario' => ((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50)),
                    'costo_total' => 5 * (((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50))),
                    'stock_anterior' => 125 + ($index * 50),
                    'costo_promedio_anterior' => ((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50)),
                    'stock_posterior' => 120 + ($index * 50),
                    'costo_promedio_posterior' => ((100 + ($index * 50)) * (15.50 + ($index * 5)) + 50 * (16.00 + ($index * 2))) / (150 + ($index * 50)),
                    'moneda' => false,
                    'documento_tipo' => 'AJUSTE',
                    'documento_numero' => 'AJ-NEG-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'documento_fecha' => $fechaBase->copy()->addDays($index + 15),
                    'observaciones' => 'Ajuste por productos dañados - Revisión de inventario',
                    'ubicacion' => 'BODEGA-A',
                    'activo' => true,
                    'es_reversible' => false,
                    'created_by' => $usuario->id,
                    'is_synced' => true,
                    'synced_at' => now(),
                    'version' => 1
                ]);
            }
        }

        // Movimientos en dólares para el primer producto
        $primerProducto = $productos->first();
        
        // Compra en dólares
        InventarioMovimiento::create([
            'producto_id' => $primerProducto->id,
            'tipo_movimiento' => 'entrada',
            'subtipo_movimiento' => 'compra',
            'cantidad' => 30,
            'costo_unitario' => 2.50, // En dólares
            'costo_total' => 30 * 2.50,
            'stock_anterior' => 0, // Stock en dólares independiente
            'costo_promedio_anterior' => 0,
            'stock_posterior' => 30,
            'costo_promedio_posterior' => 2.50,
            'moneda' => true, // Dólares
            'documento_tipo' => 'FACTURA',
            'documento_numero' => 'FC-USD-001',
            'documento_fecha' => $fechaBase->copy()->addDays(20),
            'proveedor_id' => $usuarios->skip(1)->first()?->id,
            'observaciones' => 'Compra en dólares - Proveedor internacional',
            'ubicacion' => 'BODEGA-B',
            'lote' => 'LOTE-USD-001',
            'fecha_vencimiento' => $fechaBase->copy()->addMonths(18),
            'activo' => true,
            'es_reversible' => true,
            'created_by' => $usuario->id,
            'is_synced' => true,
            'synced_at' => now(),
            'version' => 1
        ]);

        // Venta en dólares
        InventarioMovimiento::create([
            'producto_id' => $primerProducto->id,
            'tipo_movimiento' => 'salida',
            'subtipo_movimiento' => 'venta',
            'cantidad' => 10,
            'costo_unitario' => 2.50,
            'costo_total' => 10 * 2.50,
            'stock_anterior' => 30,
            'costo_promedio_anterior' => 2.50,
            'stock_posterior' => 20,
            'costo_promedio_posterior' => 2.50,
            'moneda' => true, // Dólares
            'documento_tipo' => 'FACTURA',
            'documento_numero' => 'FV-USD-001',
            'documento_fecha' => $fechaBase->copy()->addDays(25),
            'cliente_id' => $usuarios->skip(2)->first()?->id,
            'observaciones' => 'Venta en dólares - Cliente exportador',
            'ubicacion' => 'BODEGA-B',
            'lote' => 'LOTE-USD-001',
            'activo' => true,
            'es_reversible' => true,
            'created_by' => $usuario->id,
            'is_synced' => true,
            'synced_at' => now(),
            'version' => 1
        ]);

        $this->command->info('✅ Seeders de movimientos de inventario creados exitosamente.');
        $this->command->info('📊 Se crearon movimientos para ' . $productos->take(5)->count() . ' productos.');
        $this->command->info('💰 Incluye movimientos en Córdobas y Dólares.');
        $this->command->info('📋 Tipos: Ajustes iniciales, Compras, Ventas y Ajustes por daños.');
    }
}
