<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\InventarioKardex;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventarioKardex>
 */
class InventarioKardexFactory extends Factory
{
    protected $model = InventarioKardex::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 100);
        $costoUnitario = $this->faker->randomFloat(2, 5, 50);
        $stockAnterior = $this->faker->numberBetween(0, 500);
        $valorAnterior = $stockAnterior * $this->faker->randomFloat(2, 5, 45);
        $costoPromedioAnterior = $stockAnterior > 0 ? $valorAnterior / $stockAnterior : 0;
        
        // Determinar si es entrada o salida
        $esEntrada = $this->faker->boolean(60);
        $tipoMovimiento = $esEntrada ? 
            $this->faker->randomElement(['entrada', 'ajuste_positivo']) : 
            $this->faker->randomElement(['salida', 'ajuste_negativo']);
        
        $valorMovimiento = $cantidad * $costoUnitario;
        
        if ($esEntrada) {
            $stockPosterior = $stockAnterior + $cantidad;
            $valorPosterior = $valorAnterior + $valorMovimiento;
        } else {
            $stockPosterior = max(0, $stockAnterior - $cantidad);
            $valorPosterior = $valorAnterior - ($cantidad * $costoPromedioAnterior);
        }
        
        $costoPromedioPosterior = $stockPosterior > 0 ? $valorPosterior / $stockPosterior : 0;
        
        $fechaMovimiento = $this->faker->dateTimeBetween('-1 year', 'now');
        $periodo = Carbon::parse($fechaMovimiento);

        return [
            'producto_id' => Producto::factory(),
            'movimiento_id' => InventarioMovimiento::factory(),
            'tipo_movimiento' => $tipoMovimiento,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'stock_anterior' => $stockAnterior,
            'valor_anterior' => $valorAnterior,
            'costo_promedio_anterior' => $costoPromedioAnterior,
            'valor_movimiento' => $valorMovimiento,
            'stock_posterior' => $stockPosterior,
            'valor_posterior' => $valorPosterior,
            'costo_promedio_posterior' => $costoPromedioPosterior,
            'moneda' => $this->faker->boolean(20), // 20% en dólares
            'documento_tipo' => $this->faker->randomElement(['FACTURA', 'RECIBO', 'AJUSTE', 'TRANSFERENCIA', 'NOTA_CREDITO']),
            'documento_numero' => $this->faker->unique()->regexify('[A-Z]{2}-[0-9]{4}'),
            'documento_fecha' => $fechaMovimiento,
            'observaciones' => $this->faker->optional(0.6)->sentence(),
            'lote' => $this->faker->optional(0.5)->regexify('LOTE-[0-9]{4}-[0-9]{3}'),
            'fecha_vencimiento' => $this->faker->optional(0.3)->dateTimeBetween('now', '+2 years'),
            'periodo_year' => $periodo->year,
            'periodo_month' => $periodo->month,
            'fecha_movimiento' => $fechaMovimiento,
            'activo' => $this->faker->boolean(95),
            'es_ajuste_inicial' => $this->faker->boolean(10),
            'es_cierre_periodo' => $this->faker->boolean(5),
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
            'cambios' => [],
            'is_synced' => $this->faker->boolean(90),
            'synced_at' => $this->faker->boolean(90) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'updated_locally_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'version' => $this->faker->numberBetween(1, 5)
        ];
    }

    /**
     * Estado para registros de entrada en el kardex
     */
    public function entrada()
    {
        return $this->state(function (array $attributes) {
            $cantidad = $this->faker->numberBetween(10, 200);
            $costoUnitario = $this->faker->randomFloat(2, 8, 60);
            $stockAnterior = $this->faker->numberBetween(0, 300);
            $valorAnterior = $stockAnterior * $this->faker->randomFloat(2, 5, 55);
            $costoPromedioAnterior = $stockAnterior > 0 ? $valorAnterior / $stockAnterior : 0;
            
            $valorMovimiento = $cantidad * $costoUnitario;
            $stockPosterior = $stockAnterior + $cantidad;
            $valorPosterior = $valorAnterior + $valorMovimiento;
            $costoPromedioPosterior = $stockPosterior > 0 ? $valorPosterior / $stockPosterior : 0;

            return [
                'tipo_movimiento' => 'entrada',
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'stock_anterior' => $stockAnterior,
                'valor_anterior' => $valorAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'valor_movimiento' => $valorMovimiento,
                'stock_posterior' => $stockPosterior,
                'valor_posterior' => $valorPosterior,
                'costo_promedio_posterior' => $costoPromedioPosterior,
                'documento_tipo' => $this->faker->randomElement(['FACTURA', 'RECIBO', 'TRANSFERENCIA'])
            ];
        });
    }

    /**
     * Estado para registros de salida en el kardex
     */
    public function salida()
    {
        return $this->state(function (array $attributes) {
            $cantidad = $this->faker->numberBetween(5, 100);
            $stockAnterior = $this->faker->numberBetween($cantidad, 500); // Asegurar stock suficiente
            $costoPromedioAnterior = $this->faker->randomFloat(2, 10, 50);
            $valorAnterior = $stockAnterior * $costoPromedioAnterior;
            
            $valorMovimiento = $cantidad * $costoPromedioAnterior;
            $stockPosterior = $stockAnterior - $cantidad;
            $valorPosterior = $valorAnterior - $valorMovimiento;
            $costoPromedioPosterior = $costoPromedioAnterior; // Se mantiene igual en salidas

            return [
                'tipo_movimiento' => 'salida',
                'cantidad' => $cantidad,
                'costo_unitario' => $costoPromedioAnterior, // Usar costo promedio para salidas
                'stock_anterior' => $stockAnterior,
                'valor_anterior' => $valorAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'valor_movimiento' => $valorMovimiento,
                'stock_posterior' => $stockPosterior,
                'valor_posterior' => $valorPosterior,
                'costo_promedio_posterior' => $costoPromedioPosterior,
                'documento_tipo' => $this->faker->randomElement(['FACTURA', 'NOTA_CREDITO', 'TRANSFERENCIA'])
            ];
        });
    }

    /**
     * Estado para ajustes iniciales
     */
    public function ajusteInicial()
    {
        return $this->state(function (array $attributes) {
            $cantidad = $this->faker->numberBetween(50, 500);
            $costoUnitario = $this->faker->randomFloat(2, 5, 40);
            $valorMovimiento = $cantidad * $costoUnitario;

            return [
                'tipo_movimiento' => 'ajuste_positivo',
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'stock_anterior' => 0,
                'valor_anterior' => 0,
                'costo_promedio_anterior' => 0,
                'valor_movimiento' => $valorMovimiento,
                'stock_posterior' => $cantidad,
                'valor_posterior' => $valorMovimiento,
                'costo_promedio_posterior' => $costoUnitario,
                'documento_tipo' => 'AJUSTE',
                'documento_numero' => 'AJ-INICIAL-' . $this->faker->unique()->numberBetween(1000, 9999),
                'es_ajuste_inicial' => true,
                'observaciones' => 'Inventario inicial del producto'
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
            $stockAnterior = $this->faker->numberBetween(10, 200);
            $costoPromedioAnterior = $this->faker->randomFloat(2, 5, 35);
            $valorAnterior = $stockAnterior * $costoPromedioAnterior;
            
            $valorMovimiento = $cantidad * $costoUnitario;
            $stockPosterior = $stockAnterior + $cantidad;
            $valorPosterior = $valorAnterior + $valorMovimiento;
            $costoPromedioPosterior = $stockPosterior > 0 ? $valorPosterior / $stockPosterior : 0;

            return [
                'tipo_movimiento' => 'ajuste_positivo',
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario,
                'stock_anterior' => $stockAnterior,
                'valor_anterior' => $valorAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'valor_movimiento' => $valorMovimiento,
                'stock_posterior' => $stockPosterior,
                'valor_posterior' => $valorPosterior,
                'costo_promedio_posterior' => $costoPromedioPosterior,
                'documento_tipo' => 'AJUSTE',
                'documento_numero' => 'AJ-POS-' . $this->faker->unique()->numberBetween(1000, 9999),
                'observaciones' => 'Ajuste positivo de inventario'
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
            $stockAnterior = $this->faker->numberBetween($cantidad, 300);
            $costoPromedioAnterior = $this->faker->randomFloat(2, 8, 45);
            $valorAnterior = $stockAnterior * $costoPromedioAnterior;
            
            $valorMovimiento = $cantidad * $costoPromedioAnterior;
            $stockPosterior = $stockAnterior - $cantidad;
            $valorPosterior = $valorAnterior - $valorMovimiento;
            $costoPromedioPosterior = $costoPromedioAnterior;

            return [
                'tipo_movimiento' => 'ajuste_negativo',
                'cantidad' => $cantidad,
                'costo_unitario' => $costoPromedioAnterior,
                'stock_anterior' => $stockAnterior,
                'valor_anterior' => $valorAnterior,
                'costo_promedio_anterior' => $costoPromedioAnterior,
                'valor_movimiento' => $valorMovimiento,
                'stock_posterior' => $stockPosterior,
                'valor_posterior' => $valorPosterior,
                'costo_promedio_posterior' => $costoPromedioPosterior,
                'documento_tipo' => 'AJUSTE',
                'documento_numero' => 'AJ-NEG-' . $this->faker->unique()->numberBetween(1000, 9999),
                'observaciones' => $this->faker->randomElement([
                    'Ajuste por productos dañados',
                    'Ajuste por pérdida',
                    'Ajuste por vencimiento',
                    'Corrección de inventario'
                ])
            ];
        });
    }

    /**
     * Estado para registros en dólares
     */
    public function enDolares()
    {
        return $this->state(function (array $attributes) {
            $costoUnitario = $this->faker->randomFloat(2, 1, 15);
            $valorMovimiento = $attributes['cantidad'] * $costoUnitario;
            $stockPosterior = $attributes['stock_anterior'] + 
                (in_array($attributes['tipo_movimiento'], ['entrada', 'ajuste_positivo']) ? 
                    $attributes['cantidad'] : -$attributes['cantidad']);
            
            return [
                'moneda' => true,
                'costo_unitario' => $costoUnitario,
                'valor_movimiento' => $valorMovimiento,
                'valor_anterior' => $attributes['stock_anterior'] * $this->faker->randomFloat(2, 1, 12),
                'valor_posterior' => $stockPosterior * $costoUnitario,
                'costo_promedio_anterior' => $this->faker->randomFloat(2, 1, 12),
                'costo_promedio_posterior' => $costoUnitario,
                'documento_numero' => 'USD-' . $this->faker->unique()->regexify('[A-Z]{2}-[0-9]{4}')
            ];
        });
    }

    /**
     * Estado para registros en córdobas
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
     * Estado para registros con lote
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
     * Estado para cierre de período
     */
    public function cierrePeriodo()
    {
        return $this->state(function (array $attributes) {
            $fechaCierre = $this->faker->dateTimeBetween('-6 months', '-1 month');
            $periodo = Carbon::parse($fechaCierre);
            
            return [
                'es_cierre_periodo' => true,
                'documento_tipo' => 'CIERRE',
                'documento_numero' => 'CIERRE-' . $periodo->format('Y-m'),
                'documento_fecha' => $fechaCierre,
                'fecha_movimiento' => $fechaCierre,
                'periodo_year' => $periodo->year,
                'periodo_month' => $periodo->month,
                'observaciones' => 'Cierre de período contable ' . $periodo->format('Y-m')
            ];
        });
    }

    /**
     * Estado para registros no sincronizados
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
