<?php

use App\Http\Controllers\Api\V1\ReporteUtilidadInventarioController;

Route::prefix('reportes/utilidad-inventario')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ReporteUtilidadInventarioController::class, 'index'])
        ->middleware('permission:inventario.reportes_utilidad.ver');

    Route::get('/exportar-pdf', [ReporteUtilidadInventarioController::class, 'exportarPDF'])
        ->middleware('permission:inventario.reportes_utilidad.exportar');

    Route::get('/exportar-excel', [ReporteUtilidadInventarioController::class, 'exportarExcel'])
        ->middleware('permission:inventario.reportes_utilidad.exportar');
});
