<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductosExport implements FromCollection, WithHeadings, WithMapping
{
    protected $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return [
            'Código',
            'Producto',
            'Mínimo',
            'Máximo',
            'Stock Actual',
            'Precio Venta',
        ];
    }

    public function map($producto): array
    {
        return [
            $producto->codigo,
            $producto->nombre,
            $producto->stock_minimo,
            $producto->stock_maximo,
            $producto->stock_actual,
            $producto->precio_venta,
        ];
    }
}
