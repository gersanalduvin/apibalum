<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\InventarioMovimiento;
use App\Models\Audit;
use App\Models\User;
use App\Services\MovimientoInventarioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateInitialStockFromAudit extends Command
{
    protected $signature = 'inventory:create-initial-stock {--dry-run : Show what would be created}';
    protected $description = 'Create initial stock movements from David Cruz audit records';

    public function handle(MovimientoInventarioService $service)
    {
        $this->info('🔍 Buscando productos sin inventario inicial...');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔸 MODO DRY-RUN: No se crearán movimientos');
        }

        // ID de David Cruz (Confirmado por audits: 325)
        $davidId = 325;
        $this->info("ℹ️  Usando auditorias de David Cruz (ID: {$davidId}) para recuperar stock inicial.");

        // Buscar productos que NO tienen "Inventario Inicial"
        $todosLosProductos = Producto::pluck('id');
        $productosSinInicial = [];

        foreach ($todosLosProductos as $productoId) {
            $tieneInicial = DB::table('inventario_movimientos')
                ->where('producto_id', $productoId)
                ->where('observaciones', 'like', '%Inventario Inicial%')
                ->exists();

            if (!$tieneInicial) {
                // 1. Verificar si tiene audits de David Cruz
                $tieneAudits = DB::table('audits')
                    ->where('model_type', 'App\Models\Producto')
                    ->where('model_id', $productoId)
                    ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
                    ->where('user_id', $davidId)
                    ->exists();

                // 2. Si no, verificar si tiene audits de CUALQUIER usuario con stock > 0 (Fallback)
                if (!$tieneAudits) {
                    $tieneAudits = DB::table('audits')
                        ->where('model_type', 'App\Models\Producto')
                        ->where('model_id', $productoId)
                        ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
                        ->whereRaw("CAST(JSON_EXTRACT(old_values, '$.stock_actual') AS DECIMAL(10,2)) > 0")
                        ->exists();
                }

                if ($tieneAudits) {
                    $productosSinInicial[] = $productoId;
                }
            }
        }

        $this->info("📊 Encontrados " . count($productosSinInicial) . " productos para procesar");

        if (empty($productosSinInicial)) {
            $this->info('✅ Todos los productos tienen inventario inicial o no tienen audits de David');
            return 0;
        }

        $bar = $this->output->createProgressBar(count($productosSinInicial));
        $bar->start();

        $created = 0;
        $skipped = 0;

        foreach ($productosSinInicial as $productoId) {
            $producto = Producto::find($productoId);

            if (!$producto) {
                $bar->advance();
                continue;
            }

            // Buscar audits de David Cruz ordenados por fecha
            $audits = Audit::where('model_type', 'App\Models\Producto')
                ->where('model_id', $productoId)
                ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
                ->where('user_id', $davidId)
                ->orderBy('created_at', 'asc')
                ->get();

            // NUEVO FILTRO INTELIGENTE:
            // Si encontramos un audit de David donde resetea a 1 (ej. 30 -> 1),
            // NO LO IGNORAMOS, sino que USAMOS EL VALOR VIEJO (30) como la verdad.
            // Esto es porque el usuario dice "omitir" el cambio a 1, lo que implica que el 30 era lo correcto.

            $stockRecuperadoDeReset = null;
            $fechaRecuperadaDeReset = null;

            $audits = $audits->map(function ($audit) use ($davidId, &$stockRecuperadoDeReset, &$fechaRecuperadaDeReset) {
                $newStock = $audit->new_values['stock_actual'] ?? null;
                $oldStock = $audit->old_values['stock_actual'] ?? null;

                // Detectamos el patrón "Reset a 1"
                if ($audit->user_id == $davidId && !is_null($newStock) && (int)$newStock == 1) {
                    $this->warn("      ⚠️  Audit {$audit->id} es un RESET A 1. Usaremos el valor anterior ({$oldStock}) como base.");

                    // Guardamos este valor "30" para forzarlo como base más adelante
                    if ($oldStock > 1) {
                        $stockRecuperadoDeReset = $oldStock;
                        $fechaRecuperadaDeReset = $audit->created_at;
                    }

                    // Marcamos este audit para NO procesarlo como movimiento normal (diferencia),
                    // pero ya habremos capturado su 'old_value' para la base.
                    $audit->ignore_move = true;
                    return $audit;
                }
                $audit->ignore_move = false;
                return $audit;
            });

            // Filtramos los marcados (aunque en realidad los queremos mantener en la colección para saber fechas,
            // en este script iteramos para crear 'diferencias', así que mejor los sacamos del loop de diferencias
            // pero usamos la variable capturada $stockRecuperadoDeReset para la BASE).

            // Si detectamos un reset válido, forzamos la base con ese valor
            if ($stockRecuperadoDeReset) {
                $this->info("      💎 FORZANDO BASE desde Reset: {$stockRecuperadoDeReset} (Fecha: {$fechaRecuperadaDeReset})");
                // Simulamos un "primer audit" falso para que la lógica de abajo cree la base con 30
                $primerAudit = new Audit();
                $primerAudit->id = 999999; // ID ficticio
                $primerAudit->created_at = $fechaRecuperadaDeReset;
                $primerAudit->old_values = ['stock_actual' => $stockRecuperadoDeReset];
                // Reemplazamos/Prependemos a la colección para que sea el primero
                $audits->prepend($primerAudit);
            }

            // Si después del filtro nos quedamos sin audits de David, intentamos el fallback
            if ($audits->isEmpty()) {
                $audits = Audit::where('model_type', 'App\Models\Producto')
                    ->where('model_id', $productoId)
                    ->whereRaw("JSON_EXTRACT(old_values, '$.stock_actual') IS NOT NULL")
                    ->whereRaw("CAST(JSON_EXTRACT(old_values, '$.stock_actual') AS DECIMAL(10,2)) > 0")
                    ->orderBy('created_at', 'asc')
                    ->limit(50)
                    ->get();

                if ($audits->isNotEmpty()) {
                    $this->info("   ⚠️  No se encontraron audits válidos de David (Filtro 'Stock=1' aplicado). Usando Fallback.");
                }
            }

            if ($audits->isEmpty()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $this->newLine();
            $this->info("✨ {$producto->nombre} (ID: {$productoId})");
            $this->info("   Audits de David encontrados: {$audits->count()}");

            if (!$dryRun) {
                // 1. Crear BASE del stock usando el VALOR ANTIGUO del PRIMER audit
                $primerAudit = $audits->first();
                $stockBase = $primerAudit->old_values['stock_actual'] ?? 0;
                $fechaBase = Carbon::parse($primerAudit->created_at)->subSeconds(5);
                $costoUnitario = $producto->costo_promedio ?? 0;

                if ($stockBase > 0) {
                    // LÓGICA DE FECHAS PARA DUPLICADOS:
                    // Buscamos si existe una "Entrada Masiva" para este producto.
                    $entradaMasiva = InventarioMovimiento::where('producto_id', $productoId)
                        ->where('observaciones', 'like', '%Entrada Masiva%')
                        ->first();

                    if ($entradaMasiva) {
                        $fechaMasiva = Carbon::parse($entradaMasiva->documento_fecha);

                        // CASO 1: La Entrada Masiva es ANTERIOR o IGUAL a la Auditoría (ej. P051)
                        // Significa que la masiva es el origen real y la auditoría es posterior.
                        // ACCIÓN: RESPETAMOS la Masiva y NO creamos la base recuperada.
                        if ($fechaMasiva->lte($fechaBase)) {
                            $this->warn("      ⚠️  Saltando base: Existe Entrada Masiva PREVIA ({$fechaMasiva->format('d/m')}) vs Audit ({$fechaBase->format('d/m')}).");
                            $skipped++;
                            // Saltamos la creación de base, pero permitimos que se procesen los ajustes posteriores del loop.
                        }
                        // CASO 2: La Entrada Masiva es POSTERIOR a la Auditoría (ej. P037)
                        // Significa que la auditoría es el origen histórico real y la masiva es un duplicado tardío.
                        // ACCIÓN: CREAMOS la base histórica y BORRAMOS la masiva duplicada.
                        else {
                            $this->info("      🔄 Corrigiendo historia: Audit ({$fechaBase->format('d/m')}) es anterior a Masiva ({$fechaMasiva->format('d/m')}). Reemplazando Masiva.");
                            $entradaMasiva->delete();

                            // Procede a crear la base
                            $movBase = new InventarioMovimiento();
                            $movBase->fill([
                                'producto_id' => $productoId,
                                'tipo_movimiento' => 'entrada',
                                'cantidad' => $stockBase,
                                'documento_tipo' => 'ajuste',
                                'documento_numero' => 'AUDIT-BASE-' . $primerAudit->id,
                                'documento_fecha' => $fechaBase,
                                'observaciones' => 'Inventario Inicial (Base Recuperada David Cruz)',
                                'stock_anterior' => 0,
                                'stock_posterior' => $stockBase,
                                'costo_unitario' => $costoUnitario,
                                'costo_total' => $stockBase * $costoUnitario,
                                'valor_total' => $stockBase * $costoUnitario,
                                'created_by' => $davidId,
                            ]);
                            $movBase->created_at = $fechaBase;
                            $movBase->updated_at = $fechaBase;
                            $movBase->timestamps = false;
                            $movBase->save();

                            $created++;
                            $this->info("      ➕ Base creada: {$stockBase}");
                        }
                    } else {
                        // NO hay masiva, comportamiento normal
                        $movBase = new InventarioMovimiento();
                        $movBase->fill([
                            'producto_id' => $productoId,
                            'tipo_movimiento' => 'entrada',
                            'cantidad' => $stockBase,
                            'documento_tipo' => 'ajuste',
                            'documento_numero' => 'AUDIT-BASE-' . $primerAudit->id,
                            'documento_fecha' => $fechaBase,
                            'observaciones' => 'Inventario Inicial (Base Recuperada David Cruz)',
                            'stock_anterior' => 0,
                            'stock_posterior' => $stockBase,
                            'costo_unitario' => $costoUnitario,
                            'costo_total' => $stockBase * $costoUnitario,
                            'valor_total' => $stockBase * $costoUnitario,
                            'created_by' => $davidId,
                        ]);
                        $movBase->created_at = $fechaBase;
                        $movBase->updated_at = $fechaBase;
                        $movBase->timestamps = false;
                        $movBase->save();

                        $created++;
                        $this->info("      ➕ Base creada: {$stockBase}");
                    }
                }

                // 2. Procesar los cambios de los audits de David
                foreach ($audits as $audit) {
                    // Si marcamos este audit como "reset" para usar de base, no debemos procesar su diferencia
                    if (isset($audit->ignore_move) && $audit->ignore_move) {
                        continue;
                    }

                    $stockAnterior = $audit->old_values['stock_actual'] ?? 0;
                    $stockNuevo = $audit->new_values['stock_actual'] ?? 0;
                    $diferencia = $stockNuevo - $stockAnterior;

                    if ($diferencia == 0) {
                        continue;
                    }

                    $tipoMovimiento = $diferencia > 0 ? 'entrada' : 'salida';
                    $cantidad = abs($diferencia);
                    $fechaAudit = Carbon::parse($audit->created_at);

                    // Verificar duplicados (mismo día y cantidad)
                    $duplicado = InventarioMovimiento::where('producto_id', $productoId)
                        ->where('tipo_movimiento', $tipoMovimiento)
                        ->whereBetween('cantidad', [$cantidad - 0.01, $cantidad + 0.01])
                        ->whereDate('documento_fecha', $fechaAudit->toDateString())
                        ->exists();

                    if (!$duplicado) {
                        $mov = new InventarioMovimiento();
                        $mov->fill([
                            'producto_id' => $productoId,
                            'tipo_movimiento' => $tipoMovimiento,
                            'cantidad' => $cantidad,
                            'documento_tipo' => 'ajuste',
                            'documento_numero' => 'AUDIT-' . $audit->id,
                            'documento_fecha' => $fechaAudit,
                            'observaciones' => "Ajuste Auditoría David Cruz ({$audit->id})",
                            'stock_anterior' => $stockAnterior,
                            'stock_posterior' => $stockNuevo,
                            'costo_unitario' => $costoUnitario,
                            'costo_total' => $cantidad * $costoUnitario,
                            'valor_total' => $cantidad * $costoUnitario,
                            'created_by' => $davidId,
                        ]);
                        $mov->created_at = $fechaAudit;
                        $mov->updated_at = $fechaAudit;
                        $mov->timestamps = false; // Forzar fecha
                        $mov->save();

                        $created++;
                    }
                }

                $service->recalculateStockHistory($productoId);
            } else {
                $this->info("   [DRY RUN] Se crearía base y movimientos de {$audits->count()} audits");
            }

            $bar->advance();
        }

        $bar->finish();

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔸 Esto fue un DRY-RUN. Para aplicar los cambios, ejecuta sin --dry-run');
        }

        return 0;
    }
}
