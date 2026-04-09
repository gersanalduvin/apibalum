<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\Audit;
use App\Models\InventarioMovimiento;
use App\Services\MovimientoInventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FixAllProductStockHistory extends Command
{
    protected $signature = 'inventory:fix-all-stock {--dry-run : Show what would be done without making changes}';
    protected $description = 'Fix stock history for all products by creating initial stock from audit logs';

    public function handle(MovimientoInventarioService $movimientoService)
    {
        $this->info('🔍 Analizando productos...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se harán cambios en la base de datos');
        }

        // Encontrar productos que necesitan stock inicial
        // 1. Productos con movimientos negativos
        $productosConNegativos = DB::table('inventario_movimientos')
            ->select('producto_id')
            ->where('stock_posterior', '<', 0)
            ->groupBy('producto_id')
            ->pluck('producto_id')
            ->toArray();

        // 2. Productos que tienen movimientos pero NO tienen stock inicial
        $productosSinInicial = DB::select("
            SELECT DISTINCT m.producto_id 
            FROM inventario_movimientos m
            WHERE m.producto_id NOT IN (
                SELECT producto_id 
                FROM inventario_movimientos 
                WHERE documento_numero LIKE 'INIT-STOCK-%'
            )
            AND m.producto_id IN (
                SELECT id FROM inventario_producto WHERE deleted_at IS NULL
            )
        ");

        $productosSinInicial = array_map(fn($p) => $p->producto_id, $productosSinInicial);

        // Combinar ambas listas (sin duplicados)
        $productosAfectados = array_unique(array_merge($productosConNegativos, $productosSinInicial));

        $this->info("📊 Encontrados:");
        $this->info("   - " . count($productosConNegativos) . " productos con stock negativo");
        $this->info("   - " . count($productosSinInicial) . " productos sin stock inicial");
        $this->info("   - " . count($productosAfectados) . " productos totales a procesar");

        if (empty($productosAfectados)) {
            $this->info('✅ Todos los productos tienen stock inicial correcto!');
            return 0;
        }

        $bar = $this->output->createProgressBar(count($productosAfectados));
        $bar->start();

        $fixed = 0;
        $errors = 0;

        foreach ($productosAfectados as $productoId) {
            try {
                $producto = Producto::find($productoId);
                if (!$producto) {
                    $this->newLine();
                    $this->error("❌ Producto ID {$productoId} no encontrado");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // Buscar el primer cambio de stock en auditoría
                $primerCambio = Audit::where('model_type', Producto::class)
                    ->where('model_id', $productoId)
                    ->where('event', 'updated')
                    ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
                    ->orderBy('created_at', 'asc')
                    ->first();

                if (!$primerCambio) {
                    $this->newLine();
                    $this->warn("⚠️  {$producto->nombre} (ID: {$productoId}): No se encontró auditoría de stock");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $stockInicial = (float) ($primerCambio->old_values['stock_actual'] ?? 0);
                $fechaPrimerCambio = Carbon::parse($primerCambio->created_at);

                if ($stockInicial <= 0) {
                    $this->newLine();
                    $this->warn("⚠️  {$producto->nombre}: Stock inicial es 0 o negativo en auditoría");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // Verificar si ya existe un movimiento inicial
                $yaExisteInicial = InventarioMovimiento::where('producto_id', $productoId)
                    ->where('documento_numero', 'LIKE', 'INIT-STOCK-%')
                    ->exists();

                if ($yaExisteInicial) {
                    $this->newLine();
                    $this->info("ℹ️  {$producto->nombre}: Ya tiene stock inicial, solo recalculando...");
                    if (!$dryRun) {
                        $movimientoService->recalculateStockHistory($productoId);
                    }
                    $fixed++;
                    $bar->advance();
                    continue;
                }

                $this->newLine();
                $this->info("✨ {$producto->nombre} (ID: {$productoId})");
                $this->info("   Stock inicial: {$stockInicial} (fecha: {$fechaPrimerCambio->format('Y-m-d')})");

                if (!$dryRun) {
                    // Crear movimiento de stock inicial (un día antes del primer cambio)
                    $fechaInicial = $fechaPrimerCambio->copy()->subDay();

                    DB::table('inventario_movimientos')->insert([
                        'uuid' => (string)Str::uuid(),
                        'producto_id' => $productoId,
                        'tipo_movimiento' => 'entrada',
                        'subtipo_movimiento' => 'comercial',
                        'cantidad' => $stockInicial,
                        'costo_unitario' => $producto->costo_promedio ?? 0,
                        'costo_total' => $stockInicial * ($producto->costo_promedio ?? 0),
                        'stock_anterior' => 0,
                        'stock_posterior' => $stockInicial,
                        'costo_promedio_anterior' => $producto->costo_promedio ?? 0,
                        'costo_promedio_posterior' => $producto->costo_promedio ?? 0,
                        'moneda' => $producto->moneda ?? 0,
                        'documento_tipo' => 'AJUSTE',
                        'documento_numero' => 'INIT-STOCK-P' . $productoId,
                        'documento_fecha' => $fechaInicial->format('Y-m-d H:i:s'),
                        'activo' => 1,
                        'observaciones' => "Inventario Inicial (Recuperado de Auditoría - Stock: {$stockInicial})",
                        'created_by' => 1,
                        'updated_by' => 1,
                        'version' => 1,
                        'created_at' => $fechaInicial->format('Y-m-d H:i:s'),
                        'updated_at' => $fechaInicial->format('Y-m-d H:i:s')
                    ]);

                    // Verificar si hubo cambio manual (stock_actual cambió en la auditoría)
                    $stockDespuesCambio = (float) ($primerCambio->new_values['stock_actual'] ?? $stockInicial);
                    if ($stockDespuesCambio != $stockInicial) {
                        $diferencia = $stockInicial - $stockDespuesCambio;
                        $this->info("   Creando ajuste manual: {$stockInicial} -> {$stockDespuesCambio} (diferencia: {$diferencia})");

                        DB::table('inventario_movimientos')->insert([
                            'uuid' => (string)Str::uuid(),
                            'producto_id' => $productoId,
                            'tipo_movimiento' => $diferencia > 0 ? 'salida' : 'entrada',
                            'subtipo_movimiento' => null,
                            'cantidad' => abs($diferencia),
                            'costo_unitario' => $producto->costo_promedio ?? 0,
                            'costo_total' => abs($diferencia) * ($producto->costo_promedio ?? 0),
                            'stock_anterior' => $stockInicial,
                            'stock_posterior' => $stockDespuesCambio,
                            'costo_promedio_anterior' => $producto->costo_promedio ?? 0,
                            'costo_promedio_posterior' => $producto->costo_promedio ?? 0,
                            'moneda' => $producto->moneda ?? 0,
                            'documento_tipo' => 'AJUSTE',
                            'documento_numero' => 'AJUSTE-AUDIT-P' . $productoId,
                            'documento_fecha' => $fechaPrimerCambio->format('Y-m-d H:i:s'),
                            'activo' => 1,
                            'observaciones' => "Ajuste manual (Recuperado de Auditoría: {$stockInicial} -> {$stockDespuesCambio})",
                            'created_by' => 1,
                            'updated_by' => 1,
                            'version' => 1,
                            'created_at' => $fechaPrimerCambio->format('Y-m-d H:i:s'),
                            'updated_at' => $fechaPrimerCambio->format('Y-m-d H:i:s')
                        ]);
                    }

                    // Recalcular historial
                    $movimientoService->recalculateStockHistory($productoId, $fechaInicial->format('Y-m-d'));
                    $this->info("   ✅ Stock inicial creado y historial recalculado");
                }

                $fixed++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error en producto ID {$productoId}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📈 Resumen:");
        $this->info("   ✅ Productos corregidos: {$fixed}");
        if ($errors > 0) {
            $this->warn("   ⚠️  Productos con errores: {$errors}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 Esto fue un DRY-RUN. Para aplicar los cambios, ejecuta sin --dry-run');
        }

        return 0;
    }
}
