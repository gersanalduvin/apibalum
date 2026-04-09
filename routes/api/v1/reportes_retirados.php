<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ReporteRetiradosController;

Route::prefix('reportes/alumnos-retirados')->middleware('check.permissions:usuarios.alumnos.retirados')->group(function () {
    Route::get('/', [ReporteRetiradosController::class, 'index']);
    Route::get('/pdf', [ReporteRetiradosController::class, 'pdf']);
    Route::get('/excel', [ReporteRetiradosController::class, 'excel']);
});
