<?php

use App\Http\Controllers\Api\V1\NotAsignaturaGradoController;
use Illuminate\Support\Facades\Route;

Route::prefix('horarios/asignaturas')->group(function () {
    Route::get('/', [NotAsignaturaGradoController::class, 'index'])
        ->middleware('check.permissions:horarios.asignaturas.index');

    Route::get('/periodos-y-grados', [NotAsignaturaGradoController::class, 'periodosYGrados'])
        ->middleware('check.permissions:horarios.asignaturas.index'); // Reusing index permission for catalogs

    // Reuse update method but with edit permission. 
    Route::put('/{id}', [NotAsignaturaGradoController::class, 'updateScheduleConfig'])
        ->middleware('check.permissions:horarios.asignaturas.edit');
});
