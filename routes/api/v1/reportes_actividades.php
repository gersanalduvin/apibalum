<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ReporteActividadesController;

Route::prefix('reportes/actividades-semana')
    ->middleware(['permission:ver.actividades_semana'])
    ->group(function () {
        Route::get('/', [ReporteActividadesController::class, 'getReporte']);
        Route::get('/generar-pdf', [ReporteActividadesController::class, 'generarPdf']);
    });
