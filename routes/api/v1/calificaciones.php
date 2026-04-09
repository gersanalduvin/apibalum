<?php

use App\Http\Controllers\Api\V1\CalificacionController;
use Illuminate\Support\Facades\Route;

Route::prefix('calificaciones')->group(function () {
    Route::get('/grupo/{grupoId}/asignatura/{asignaturaId}/corte/{corteId}', [CalificacionController::class, 'index'])
        ->middleware('check.permissions:operaciones.docentes');
    Route::post('/', [CalificacionController::class, 'store'])
        ->middleware('check.permissions:operaciones.docentes');
});
