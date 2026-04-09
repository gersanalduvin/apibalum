<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $marcas = ['BIC', 'Norma', 'Casio', 'Sharpie', 'Reprograf', 'Pilot', 'Faber-Castell', 'Staedtler'];
        $unidades = ['unidad', 'caja', 'paquete', 'resma', 'litro', 'kg', 'metro'];
        $ubicaciones = ['Estante A1', 'Estante A2', 'Estante B1', 'Estante B2', 'Bodega C1', 'Vitrina D1'];
        
        $stockActual = $this->faker->numberBetween(0, 500);
        $costoPromedio = $this->faker->randomFloat(2, 1, 1000);
        
        return [
            'uuid' => Str::uuid(),
            'codigo' => 'PROD' . $this->faker->unique()->numberBetween(1000, 9999),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->sentence(10),
            'marca' => $this->faker->randomElement($marcas),
            'modelo' => $this->faker->word(),
            'unidad_medida' => $this->faker->randomElement($unidades),
            'stock_actual' => $stockActual,
            'stock_minimo' => $this->faker->numberBetween(1, 20),
            'stock_maximo' => $stockActual + $this->faker->numberBetween(50, 200),
            'costo_promedio' => $costoPromedio,
            'precio_venta' => $costoPromedio * $this->faker->randomFloat(2, 1.2, 2.5), // Margen de ganancia
            'moneda' => $this->faker->boolean(20), // 20% probabilidad de ser dólar
            'cuenta_inventario_id' => null, // Se puede asignar después si existen cuentas
            'cuenta_costo_id' => null,
            'cuenta_venta_id' => null,
            'activo' => $this->faker->boolean(90), // 90% probabilidad de estar activo
            'permite_venta' => $this->faker->boolean(85),
            'maneja_inventario' => $this->faker->boolean(80),
            'permite_stock_negativo' => $this->faker->boolean(10),
            'ubicacion' => $this->faker->randomElement($ubicaciones),
            'codigo_barras' => $this->faker->ean13(),
            'peso' => $this->faker->randomFloat(3, 0.001, 10),
            'propiedades_adicionales' => [
                'color' => $this->faker->colorName(),
                'material' => $this->faker->randomElement(['Plástico', 'Metal', 'Papel', 'Cartón']),
                'categoria' => $this->faker->randomElement(['Oficina', 'Escolar', 'Tecnología', 'Papelería'])
            ],
            'created_by' => null, // Se puede asignar después si existen usuarios
            'updated_by' => null,
            'deleted_by' => null,
            'cambios' => null,
            'is_synced' => $this->faker->boolean(70),
            'synced_at' => $this->faker->boolean(50) ? $this->faker->dateTimeThisYear() : null,
            'updated_locally_at' => $this->faker->boolean(30) ? $this->faker->dateTimeThisMonth() : null,
            'version' => $this->faker->numberBetween(1, 5)
        ];
    }

    /**
     * Estado para productos activos
     */
    public function activo()
    {
        return $this->state(function (array $attributes) {
            return [
                'activo' => true,
                'permite_venta' => true,
            ];
        });
    }

    /**
     * Estado para productos inactivos
     */
    public function inactivo()
    {
        return $this->state(function (array $attributes) {
            return [
                'activo' => false,
                'permite_venta' => false,
            ];
        });
    }

    /**
     * Estado para productos con stock bajo
     */
    public function stockBajo()
    {
        return $this->state(function (array $attributes) {
            $stockMinimo = $this->faker->numberBetween(10, 20);
            return [
                'stock_actual' => $this->faker->numberBetween(0, $stockMinimo - 1),
                'stock_minimo' => $stockMinimo,
            ];
        });
    }

    /**
     * Estado para productos en dólares
     */
    public function enDolares()
    {
        return $this->state(function (array $attributes) {
            return [
                'moneda' => true,
            ];
        });
    }

    /**
     * Estado para productos en córdobas
     */
    public function enCordobas()
    {
        return $this->state(function (array $attributes) {
            return [
                'moneda' => false,
            ];
        });
    }

    /**
     * Estado para servicios (no manejan inventario)
     */
    public function servicio()
    {
        return $this->state(function (array $attributes) {
            return [
                'codigo' => 'SERV' . $this->faker->unique()->numberBetween(1000, 9999),
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'stock_maximo' => null,
                'maneja_inventario' => false,
                'permite_stock_negativo' => true,
                'ubicacion' => null,
                'codigo_barras' => null,
                'peso' => null,
            ];
        });
    }
}
