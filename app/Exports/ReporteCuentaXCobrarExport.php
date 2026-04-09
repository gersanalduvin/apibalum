<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReporteCuentaXCobrarExport implements FromView, ShouldAutoSize
{
    public function __construct(private array $data) {}

    public function view(): View
    {
        return view('reportes.cuenta_x_cobrar.excel', [
            'grupos' => $this->data['grupos'],
            'resumen_global' => $this->data['resumen_global'],
            'meses_cols' => $this->data['meses_cols'],
        ]);
    }
}
