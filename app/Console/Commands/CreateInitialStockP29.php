<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\MovimientoInventarioService;

class CreateInitialStockP29 extends Command
{
    protected $signature = 'inventory:create-initial-p29';
    protected $description = 'Create initial stock for Product 29 based on audit history';

    public function handle(MovimientoInventarioService $service)
    {
        $this->info('Creating initial stock movement for Product 29...');

        // Insert initial stock movement
        DB::table('inventario_movimientos')->insert([
            'uuid' => (string)Str::uuid(),
            'producto_id' => 29,
            'tipo_movimiento' => 'entrada',
            'subtipo_movimiento' => 'comercial',
            'cantidad' => 20,
            'costo_unitario' => 160,
            'costo_total' => 3200,
            'stock_anterior' => 0,
            'stock_posterior' => 20,
            'costo_promedio_anterior' => 160,
            'costo_promedio_posterior' => 160,
            'moneda' => 0,
            'documento_tipo' => 'AJUSTE',
            'documento_numero' => 'INIT-STOCK-P29',
            'documento_fecha' => '2026-01-06 00:00:00',
            'activo' => 1,
            'observaciones' => 'Inventario Inicial (Recuperado de Auditoría - Stock: 20)',
            'created_by' => 1,
            'updated_by' => 1,
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->info('Initial stock movement created successfully.');
        $this->info('Recalculating stock history...');

        // Recalculate stock history
        $service->recalculateStockHistory(29, '2026-01-06');

        $this->info('Stock history recalculated successfully.');

        return 0;
    }
}
