<?php

use App\Http\Controllers\Api\V1\ReporteCuentaXCobrarController;
use Illuminate\Support\Facades\Route;

Route::prefix('reportes/cuenta-x-cobrar')->group(function () {
    Route::get('/periodos-turnos', [ReporteCuentaXCobrarController::class, 'periodosTurnos'])
        ->middleware('check.permissions:reporte_cuenta_x_cobrar.ver');

    Route::get('/grupos', [ReporteCuentaXCobrarController::class, 'grupos'])
        ->middleware('check.permissions:reporte_cuenta_x_cobrar.ver');

    Route::get('/usuarios-aranceles', [ReporteCuentaXCobrarController::class, 'usuariosAranceles'])
        ->middleware('check.permissions:reporte_cuenta_x_cobrar.ver');

    Route::post('/export/pdf', [ReporteCuentaXCobrarController::class, 'exportPdf'])
        ->middleware('check.permissions:reporte_cuenta_x_cobrar.exportar_pdf');

    Route::post('/export/excel', [ReporteCuentaXCobrarController::class, 'exportExcel'])
        ->middleware('check.permissions:reporte_cuenta_x_cobrar.exportar_pdf');
});
