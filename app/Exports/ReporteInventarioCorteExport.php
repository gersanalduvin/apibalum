<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteInventarioCorteExport implements FromView, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    public function __construct(
        private array $data, 
        private string $fechaCorte,
        private ?string $categoriaNombre = null
    ) {}

    public function view(): View
    {
        return view('exports.reporte-stock-corte', [
            'productos' => $this->data,
            'fechaCorte' => $this->fechaCorte,
            'categoriaNombre' => $this->categoriaNombre
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            3 => ['font' => ['bold' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
