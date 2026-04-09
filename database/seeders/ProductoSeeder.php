<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Producto;
use Illuminate\Support\Str;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = [
            [
                'uuid' => Str::uuid(),
                'codigo' => 'PROD001',
                'nombre' => 'Cuaderno Universitario 100 hojas',
                'descripcion' => 'Cuaderno universitario de 100 hojas rayadas, tamaño carta',
                'unidad_medida' => 'UND',
                'stock_actual' => 50,
                'stock_minimo' => 10,
                'stock_maximo' => 100,
                'costo_promedio' => 25.00,
                'precio_venta' => 35.00,
                'moneda' => false, // Córdoba
                'activo' => true,
                'is_synced' => true,
                'version' => 1
            ],
            [
                'uuid' => Str::uuid(),
                'codigo' => 'PROD002',
                'nombre' => 'Bolígrafo BIC Azul',
                'descripcion' => 'Bolígrafo de tinta azul, punta media',
                'unidad_medida' => 'UND',
                'stock_actual' => 200,
                'stock_minimo' => 50,
                'stock_maximo' => 500,
                'costo_promedio' => 3.50,
                'precio_venta' => 5.00,
                'moneda' => false, // Córdoba
                'activo' => true,
                'is_synced' => true,
                'version' => 1
            ],
            [
                'uuid' => Str::uuid(),
                'codigo' => 'PROD003',
                'nombre' => 'Resma de Papel Bond 75g',
                'descripcion' => 'Resma de papel bond blanco, 75 gramos, tamaño carta',
                'unidad_medida' => 'PAQ',
                'stock_actual' => 25,
                'stock_minimo' => 5,
                'stock_maximo' => 50,
                'costo_promedio' => 120.00,
                'precio_venta' => 150.00,
                'moneda' => false, // Córdoba
                'activo' => true,
                'is_synced' => true,
                'version' => 1
            ],
            [
                'uuid' => Str::uuid(),
                'codigo' => 'PROD004',
                'nombre' => 'Calculadora Científica',
                'descripcion' => 'Calculadora científica con funciones trigonométricas',
                'unidad_medida' => 'UND',
                'stock_actual' => 15,
                'stock_minimo' => 3,
                'stock_maximo' => 30,
                'costo_promedio' => 450.00,
                'precio_venta' => 600.00,
                'moneda' => false, // Córdoba
                'activo' => true,
                'is_synced' => true,
                'version' => 1
            ],
            [
                'uuid' => Str::uuid(),
                'codigo' => 'PROD005',
                'nombre' => 'Marcador Permanente Negro',
                'descripcion' => 'Marcador permanente de tinta negra, punta gruesa',
                'unidad_medida' => 'UND',
                'stock_actual' => 80,
                'stock_minimo' => 20,
                'stock_maximo' => 150,
                'costo_promedio' => 15.00,
                'precio_venta' => 22.00,
                'moneda' => false, // Córdoba
                'activo' => true,
                'is_synced' => true,
                'version' => 1
            ],
            [
                'uuid' => Str::uuid(),
                'codigo' => 'SERV001',
                'nombre' => 'Servicio de Impresión B/N',
                'descripcion' => 'Servicio de impresión en blanco y negro por página',
                'unidad_medida' => 'SRV',
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'stock_maximo' => null,
                'costo_promedio' => 0.50,
                'precio_venta' => 1.00,
                'moneda' => false, // Córdoba
                'activo' => true,
                'is_synced' => true,
                'version' => 1
            ]
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
