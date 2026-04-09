<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventarioMovimiento>
 */
class InventarioMovimientoFactory extends Factory
{
    protected $model = InventarioMovimiento::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 100);
        $costoUnitario = $this->faker->randomFloat(2, 5, 50);
        $costoTotal = $cantidad * $costoUnitario;
        $stockAnterior = $this->faker->numberBetween(0, 500);
        $costoPromedioAnterior = $this->faker->randomFloat(2, 5, 45);
        
        // Determinar si es entrada o salida
        $esEntrada = $this->faker->boolean(60); // 60% probabilidad de entrada
        
        if ($esEntrada) {
            $stockPosterior = $stockAnterior + $cantidad;
            $costoPromedioPosterior = $stockPosterior > 0 ? 
                (($stockAnterior * $costoPromedioAnterior) + ($cantidad * $costoUnitario)) / $stockPosterior : 
                $costoUnitario;
        } else {
            $stockPosterior = max(0, $stockAnterior - $cantidad);
            $costoPromedioPosterior = $costoPromedioAnterior;
        }

        return [
            'producto_id' => Producto::factory(),
            'tipo_movimiento' => $esEntrada ? 
                $this->faker->randomElement(['entrada', 'ajuste_positivo']) : 
                $this->faker->randomElement(['salida', 'ajuste_negativo']),
            'subtipo_movimiento' => $this->faker->randomElement([
                'compra', 'venta', 'ajuste_inventario', 'transferencia', 
                'devolucion_compra', 'devolucion_venta', 'dano_deterioro', 
                'inventario_inicial', 'produccion', 'consumo_interno'
            ]),
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total' => $costoTotal,
            'stock_anterior' => $stockAnterior,
            'costo_promedio_anterior' => $costoPromedioAnterior,
            'stock_posterior' => $stockPosterior,
            'costo_promedio_posterior' => $costoPromedioPosterior,
            'moneda' => $this->faker->boolean(20), // 20% en dólares, 80% en córdobas
            'documento_tipo' => $this->faker->randomElement(['FACTURA', 'RECIBO', 'AJUSTE', 'TRANSFERENCIA', 'NOTA_CREDITO', 'NOTA_DEBITO']),
            'documento_numero' => $this->faker->unique()->regexify('[A-Z]{2}-[0-9]{4}'),
            'documento_fecha' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'proveedor_id' => $this->faker->boolean(40) ? User::factory() : null,
            'cliente_id' => $this->faker->boolean(40) ? User::factory() : null,
            'observaciones' => $this->faker->optional(0.7)->sentence(),
            'ubicacion' => $this->faker->randomElement(['BODEGA-A', 'BODEGA-B', 'BODEGA-C', 'ALMACEN-PRINCIPAL', 'SUCURSAL-1', 'SUCURSAL-2']),
            'lote' => $this->faker->optional(0.6)->regexify('LOTE-[0-9]{4}-[0-9]{3}'),
            'fecha_vencimiento' => $this->faker->optional(0.4)->dateTimeBetween('now', '+2 years'),
            'activo' => $this->faker->boolean(95), // 95% activos
            'es_reversible' => $this->faker->boolean(80), // 80% reversibles
            'movimiento_reverso_id' => null, // Se asigna manualmente si es necesario
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
            'cambios' => [],
            'is_synced' => $this->faker->boolean(90), // 90% sincronizados
            'synced_at' => $this->faker->boolean(90) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'updated_locally_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'version' => $this->faker->numberBetween(1, 5)
        ];
    }

    /**
     * Estado para movimientos de entrada
     */
    public function entrada()
    {
        return $this->state(function (array $attributes) {
            $cantidad = $this->faker->numberBetween(10, 200);
            $costoUnitario = $this->faker->randomFloat(2, 8, 60);
            $stockAnterior = $this->faker->numberBetween(0, 300);
            $costoPromedioAnterior = $this->faker->randomFloat(2, 5, 55);
            $stockPosterior = $stockAnterior + $cantidad;
            $costoPromedioPosterior = $stockPosterior > 0 ? 
                (($stockAnterior * $costoPromedioAnterior) + ($cantidad * $costoUnitario)) / $stockPosterior : 
                $costoUnitario;

            return [
                'tipo_movimiento' => 'entrada',
                'subtipo_movimiento' => $this->faker->randomElement(['compra', 'devolucion_venta', 'transferencia', 'produccion']),
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'costo_total' => $cantidad * $costoUnitario,
                'stock_anterior' => $stockAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'stock_posterior' => $stockPosterior,
                'costo_promedio_posterior' => $costoPromedioPosterior,
                'documento_tipo' => $this->faker->randomElement(['FACTURA', 'RECIBO', 'TRANSFERENCIA']),
                'proveedor_id' => User::factory(),
                'cliente_id' => null
            ];
        });
    }

    /**
     * Estado para movimientos de salida
     */
    public function salida()
    {
        return $this->state(function (array $attributes) {
            $cantidad = $this->faker->numberBetween(5, 100);
            $stockAnterior = $this->faker->numberBetween($cantidad, 500); // Asegurar stock suficiente
            $costoPromedioAnterior = $this->faker->randomFloat(2, 10, 50);
            $stockPosterior = $stockAnterior - $cantidad;

            return [
                'tipo_movimiento' => 'salida',
                'subtipo_movimiento' => $this->faker->randomElement(['venta', 'devolucion_compra', 'transferencia', 'consumo_interno']),
                'cantidad' => $cantidad,
                'costo_unitario' => $costoPromedioAnterior, // Usar costo promedio para salidas
                'costo_total' => $cantidad * $costoPromedioAnterior,
                'stock_anterior' => $stockAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'stock_posterior' => $stockPosterior,
                'costo_promedio_posterior' => $costoPromedioAnterior,
                'documento_tipo' => $this->faker->randomElement(['FACTURA', 'NOTA_CREDITO', 'TRANSFERENCIA']),
                'proveedor_id' => null,
                'cliente_id' => User::factory()
            ];
        });
    }

    /**
     * Estado para ajustes positivos
     */
    public function ajustePositivo()
    {
        return $this->state(function (array $attributes) {
            $cantidad = $this->faker->numberBetween(1, 50);
            $costoUnitario = $this->faker->randomFloat(2, 5, 40);
            $stockAnterior = $this->faker->numberBetween(0, 200);
            $costoPromedioAnterior = $this->faker->randomFloat(2, 5, 35);
            $stockPosterior = $stockAnterior + $cantidad;
            $costoPromedioPosterior = $stockPosterior > 0 ? 
                (($stockAnterior * $costoPromedioAnterior) + ($cantidad * $costoUnitario)) / $stockPosterior : 
                $costoUnitario;

            return [
                'tipo_movimiento' => 'ajuste_positivo',
                'subtipo_movimiento' => $this->faker->randomElement(['ajuste_inventario', 'inventario_inicial', 'correccion_error']),
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'costo_total' => $cantidad * $costoUnitario,
                'stock_anterior' => $stockAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'stock_posterior' => $stockPosterior,
                'costo_promedio_posterior' => $costoPromedioPosterior,
                'documento_tipo' => 'AJUSTE',
                'documento_numero' => 'AJ-POS-' . $this->faker->unique()->numberBetween(1000, 9999),
                'proveedor_id' => null,
                'cliente_id' => null,
                'es_reversible' => false
            ];
        });
    }

    /**
     * Estado para ajustes negativos
     */
    public function ajusteNegativo()
    {
        return $this->state(function (array $attributes) {
            $cantidad = $this->faker->numberBetween(1, 30);
            $stockAnterior = $this->faker->numberBetween($cantidad, 300); // Asegurar stock suficiente
            $costoPromedioAnterior = $this->faker->randomFloat(2, 8, 45);
            $stockPosterior = $stockAnterior - $cantidad;

            return [
                'tipo_movimiento' => 'ajuste_negativo',
                'subtipo_movimiento' => $this->faker->randomElement(['dano_deterioro', 'perdida', 'robo', 'vencimiento', 'correccion_error']),
                'cantidad' => $cantidad,
                'costo_unitario' => $costoPromedioAnterior,
                'costo_total' => $cantidad * $costoPromedioAnterior,
                'stock_anterior' => $stockAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'stock_posterior' => $stockPosterior,
                'costo_promedio_posterior' => $costoPromedioAnterior,
                'documento_tipo' => 'AJUSTE',
                'documento_numero' => 'AJ-NEG-' . $this->faker->unique()->numberBetween(1000, 9999),
                'proveedor_id' => null,
                'cliente_id' => null,
                'es_reversible' => false
            ];
        });
    }

    /**
     * Estado para movimientos en dólares
     */
    public function enDolares()
    {
        return $this->state(function (array $attributes) {
            return [
                'moneda' => true,
                'costo_unitario' => $this->faker->randomFloat(2, 1, 15), // Costos en dólares más bajos
                'costo_total' => $attributes['cantidad'] * $this->faker->randomFloat(2, 1, 15),
                'documento_numero' => 'USD-' . $this->faker->unique()->regexify('[A-Z]{2}-[0-9]{4}')
            ];
        });
    }

    /**
     * Estado para movimientos en córdobas
     */
    public function enCordobas()
    {
        return $this->state(function (array $attributes) {
            return [
                'moneda' => false,
                'documento_numero' => 'NIO-' . $this->faker->unique()->regexify('[A-Z]{2}-[0-9]{4}')
            ];
        });
    }

    /**
     * Estado para movimientos con lote
     */
    public function conLote()
    {
        return $this->state(function (array $attributes) {
            return [
                'lote' => 'LOTE-' . date('Y') . '-' . $this->faker->unique()->numberBetween(100, 999),
                'fecha_vencimiento' => $this->faker->dateTimeBetween('+6 months', '+3 years')
            ];
        });
    }

    /**
     * Estado para movimientos no sincronizados
     */
    public function noSincronizado()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_synced' => false,
                'synced_at' => null,
                'updated_locally_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'version' => $this->faker->numberBetween(1, 3)
            ];
        });
    }
}
