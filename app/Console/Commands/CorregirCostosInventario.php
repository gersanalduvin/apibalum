<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\InventarioMovimiento;
use Illuminate\Support\Facades\DB;

class CorregirCostosInventario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventario:corregir-costos {--producto_id= : ID del producto especifico a corregir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige movimientos con costo cero y recalcula el historial de inventario';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $productoId = $this->option('producto_id');

        if ($productoId) {
            $productos = Producto::where('id', $productoId)->get();
            $this->info("Procesando producto ID: {$productoId}");
        } else {
            $this->info("Procesando TODOS los productos del inventario...");
            // Asumimos que todos en esta tabla manejan inventario
            $productos = Producto::all();
        }

        $bar = $this->output->createProgressBar(count($productos));
        $bar->start();

        foreach ($productos as $producto) {
            $this->corregirProducto($producto);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Proceso completado.');

        return 0;
    }

    private function corregirProducto($producto)
    {
        // Obtener todos los movimientos ordenados cronológicamente
        $movimientos = InventarioMovimiento::where('producto_id', $producto->id)
            ->orderBy('documento_fecha', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($movimientos->isEmpty()) {
            return;
        }

        $currentStock = 0;
        $currentAvgCost = 0;
        $fixedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($movimientos as $mov) {
                // Establecer valores anteriores
                $mov->stock_anterior = $currentStock;
                $mov->costo_promedio_anterior = $currentAvgCost;

                $tipo = $mov->tipo_movimiento;

                // BACKFILL: Si no tiene precio_venta histórico, usar el actual
                if (is_null($mov->precio_venta)) {
                    $mov->precio_venta = $producto->precio_venta;
                }

                // --- LÓGICA DE ENTRADAS / AJUSTES POSITIVOS ---
                if (in_array($tipo, ['entrada', 'ajuste_positivo'])) {

                    // CORRECCIÓN: Si el costo unitario es <= 0, intentamos usar el promedio actual
                    // Esto evita diluir el costo promedio con entradas a cero.
                    if ($mov->costo_unitario <= 0) {
                        if ($currentAvgCost > 0) {
                            $mov->costo_unitario = $currentAvgCost;
                            $mov->costo_total = $mov->cantidad * $currentAvgCost;
                            $fixedCount++;
                        }
                    }

                    // Cálculo de Promedio Ponderado:
                    // (StockActual * CostoPromedioActual + CantidadEntrada * CostoEntrada) / (StockActual + CantidadEntrada)
                    $nuevoStock = $currentStock + $mov->cantidad;

                    if ($nuevoStock > 0) {
                        $totalValorAnterior = $currentStock * $currentAvgCost;
                        $totalValorEntrada = $mov->cantidad * ($mov->costo_unitario ?? 0);
                        $currentAvgCost = ($totalValorAnterior + $totalValorEntrada) / $nuevoStock;
                    } else {
                        // Si es el primer movimiento o stock cero, el costo promedio es el unitario de esta entrada
                        if ($mov->costo_unitario > 0) {
                            $currentAvgCost = $mov->costo_unitario;
                        }
                    }

                    $currentStock = $nuevoStock;
                }
                // --- LÓGICA DE SALIDAS / AJUSTES NEGATIVOS / TRANSFERENCIAS ---
                elseif (in_array($tipo, ['salida', 'ajuste_negativo', 'transferencia'])) {

                    $currentStock -= $mov->cantidad;

                    // En salidas, SIEMPRE forzamos que el costo unitario sea el promedio actual
                    // para mantener la consistencia contable y corregir desviaciones previas
                    if ($currentAvgCost > 0) {
                        $mov->costo_unitario = $currentAvgCost;
                        $mov->costo_total = $mov->cantidad * $currentAvgCost;
                    }

                    if ($currentStock <= 0) {
                        $currentStock = 0;
                        // Opcional: Resetear costo promedio si stock es 0?
                        // Generalmente se mantiene el último costo conocido.
                    }
                    // El costo promedio NO cambia en las salidas
                }

                // Establecer valores posteriores
                $mov->stock_posterior = $currentStock;
                $mov->costo_promedio_posterior = $currentAvgCost;

                // Guardar cambios sin disparar eventos de modelo para evitar bucles
                $mov->saveQuietly();

                // Actualizar Kardex asociado si existe
                if ($mov->kardex) {
                    $periodo = $mov->getPeriodoContable();
                    $mov->kardex->updateQuietly([
                        'stock_anterior' => $mov->stock_anterior,
                        'stock_posterior' => $mov->stock_posterior,
                        'costo_promedio_anterior' => $mov->costo_promedio_anterior,
                        'costo_promedio_posterior' => $mov->costo_promedio_posterior,
                        'costo_unitario' => $mov->costo_unitario, // Importante: actualizar costo en kardex también
                        'precio_venta' => $mov->precio_venta,
                        'periodo_year' => $periodo['year'],
                        'periodo_month' => $periodo['month'],
                        'fecha_movimiento' => $periodo['fecha']
                    ]);
                }
            }

            // Actualizar el producto final con los valores recalculados
            $producto->stock_actual = $currentStock;
            $producto->costo_promedio = $currentAvgCost;
            $producto->saveQuietly();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al procesar producto {$producto->id} ({$producto->codigo}): " . $e->getMessage());
        }
    }
}
