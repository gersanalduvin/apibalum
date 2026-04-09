<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\InventarioMovimiento;
use App\Services\MovimientoInventarioService;

class CheckProductStock extends Command
{
    protected $signature = 'inventory:check-product {producto_id}';
    protected $description = 'Check stock movements for a specific product';

    public function handle()
    {
        $productoId = $this->argument('producto_id');
        $producto = Producto::find($productoId);

        if (!$producto) {
            $this->error("Producto {$productoId} no encontrado");
            return 1;
        }

        $this->info("📦 Producto: {$producto->nombre} (ID: {$productoId})");
        $this->info("   Stock actual: {$producto->stock_actual}");
        $this->newLine();

        $movimientos = InventarioMovimiento::where('producto_id', $productoId)
            ->orderBy('documento_fecha')
            ->orderBy('created_at')
            ->get();

        $this->info("📊 Movimientos ({$movimientos->count()}):");
        $this->table(
            ['ID', 'Fecha', 'Tipo', 'Cantidad', 'Stock Post.', 'Observaciones'],
            $movimientos->map(fn($m) => [
                $m->id,
                $m->documento_fecha->format('Y-m-d'),
                $m->tipo_movimiento,
                $m->cantidad,
                $m->stock_posterior,
                substr($m->observaciones ?? '', 0, 40)
            ])
        );

        $negativos = $movimientos->where('stock_posterior', '<', 0)->count();
        if ($negativos > 0) {
            $this->error("⚠️  {$negativos} movimientos con stock negativo");
        } else {
            $this->info("✅ Sin movimientos negativos");
        }

        return 0;
    }
}
