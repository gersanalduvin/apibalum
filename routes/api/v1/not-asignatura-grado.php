<?php

use App\Http\Controllers\Api\V1\NotAsignaturaGradoController;
use Illuminate\Support\Facades\Route;

Route::prefix('not-asignatura-grado')->group(function () {
    Route::get('/', [NotAsignaturaGradoController::class, 'index'])
        ->middleware('check.permissions:not_asignatura_grado.index');

    Route::get('/getall', [NotAsignaturaGradoController::class, 'getall'])
        ->middleware('check.permissions:not_asignatura_grado.index');

    Route::post('/', [NotAsignaturaGradoController::class, 'store'])
        ->middleware('check.permissions:not_asignatura_grado.create');

    Route::put('/{id}', [NotAsignaturaGradoController::class, 'update'])
        ->middleware('check.permissions:not_asignatura_grado.update');

    Route::get('/periodos-y-grados', [NotAsignaturaGradoController::class, 'periodosYGrados'])
        ->middleware('check.permissions:not_asignatura_grado.index');

    Route::get('/alternativas', [NotAsignaturaGradoController::class, 'alternativas'])
        ->middleware('check.permissions:not_asignatura_grado.index');

    Route::delete('/corte/{id}', [NotAsignaturaGradoController::class, 'destroyCorte'])
        ->middleware('check.permissions:not_asignatura_grado.delete');

    Route::get('/{id}', [NotAsignaturaGradoController::class, 'show'])
        ->middleware('check.permissions:not_asignatura_grado.show');

    Route::delete('/{id}', [NotAsignaturaGradoController::class, 'destroy'])
        ->middleware('check.permissions:not_asignatura_grado.delete');

    Route::get('/export/pdf', [NotAsignaturaGradoController::class, 'exportPdf'])
        ->middleware('check.permissions:not_asignatura_grado.index');

    Route::get('/export/excel', [NotAsignaturaGradoController::class, 'exportExcel'])
        ->middleware('check.permissions:not_asignatura_grado.index');

    Route::put('/bulk/reorder', [NotAsignaturaGradoController::class, 'reorder'])
        ->middleware('check.permissions:not_asignatura_grado.update');

});
