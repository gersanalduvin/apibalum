<?php

use App\Http\Controllers\Api\V1\ReporteCierreCajaController;
use Illuminate\Support\Facades\Route;

Route::prefix('reportes/cierre-caja')->group(function () {
    Route::get('/detalles', [ReporteCierreCajaController::class, 'detalles'])
        ->middleware('check.permissions:reporte_cierre_caja.ver');

    Route::get('/conceptos', [ReporteCierreCajaController::class, 'conceptos'])
        ->middleware('check.permissions:reporte_cierre_caja.ver');

    Route::get('/paquetes', [ReporteCierreCajaController::class, 'paquetes'])
        ->middleware('check.permissions:reporte_cierre_caja.ver');

    Route::get('/detalles/pdf', [ReporteCierreCajaController::class, 'exportDetallesPdf'])
        ->middleware('check.permissions:reporte_cierre_caja.exportar_pdf');

    Route::get('/conceptos/pdf', [ReporteCierreCajaController::class, 'exportConceptosPdf'])
        ->middleware('check.permissions:reporte_cierre_caja.exportar_pdf');

    Route::get('/paquetes/pdf', [ReporteCierreCajaController::class, 'exportPaquetesPdf'])
        ->middleware('check.permissions:reporte_cierre_caja.exportar_pdf');

    Route::get('/detalles/excel', [ReporteCierreCajaController::class, 'exportDetallesExcel'])
        ->middleware('check.permissions:reporte_cierre_caja.exportar_excel');

    Route::get('/conceptos/excel', [ReporteCierreCajaController::class, 'exportConceptosExcel'])
        ->middleware('check.permissions:reporte_cierre_caja.exportar_excel');

    Route::get('/paquetes/excel', [ReporteCierreCajaController::class, 'exportPaquetesExcel'])
        ->middleware('check.permissions:reporte_cierre_caja.exportar_excel');
});
