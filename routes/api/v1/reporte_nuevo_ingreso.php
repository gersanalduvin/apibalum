<?php

use App\Http\Controllers\Api\V1\NuevoIngresoReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reportes/nuevo-ingreso')
    ->middleware('check.permissions:repote.nuevoingreso')
    ->group(function () {
        Route::get('/periodos-lectivos', [NuevoIngresoReportController::class, 'getPeriodosLectivos']);
        Route::get('/export', [NuevoIngresoReportController::class, 'exportPdf']);
    });
