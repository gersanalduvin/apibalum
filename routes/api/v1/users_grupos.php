<?php

use App\Http\Controllers\Api\V1\UsersGrupoController;
use App\Http\Controllers\Api\V1\ConfPeriodoLectivoController;
use App\Http\Controllers\Api\V1\ConfigGradoController;
use App\Http\Controllers\Api\V1\ConfigGruposController;
use App\Http\Controllers\Api\V1\ConfigTurnosController;
use Illuminate\Support\Facades\Route;

Route::prefix('users-grupos')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [UsersGrupoController::class, 'index'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::post('/', [UsersGrupoController::class, 'store'])
        ->middleware('check.permissions:usuarios.alumnos.matricular');
    Route::get('/getall', [UsersGrupoController::class, 'getAll'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::get('/{id}', [UsersGrupoController::class, 'show'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::put('/{id}', [UsersGrupoController::class, 'update'])
        ->middleware('check.permissions:usuarios.alumnos.matricular');
    Route::delete('/{id}', [UsersGrupoController::class, 'destroy'])
        ->middleware('check.permissions:usuarios.alumnos.matricular');
    Route::post('/{id}/restore', [UsersGrupoController::class, 'restore'])
        ->middleware('check.permissions:usuarios.alumnos.crear');

    // Generar PDF de ficha de inscripción
    Route::get('/{id}/ficha-inscripcion-pdf', [\App\Http\Controllers\Api\V1\UserController::class, 'generateFichaInscripcionPdf'])
        ->middleware('check.permissions:usuarios.alumnos.ver');

    // Encolar envío de ficha por email (SES + SQS)
    Route::post('/{id}/ficha-inscripcion-email', [\App\Http\Controllers\Api\V1\UserController::class, 'enqueueFichaInscripcionEmail'])
        ->middleware('check.permissions:usuarios.alumnos.ver');


    // ========================================
    // ENDPOINTS PARA CONTROLES SELECT
    // ========================================

    // Listado de períodos lectivos para select
    Route::get('/periodos-lectivos/list', [ConfPeriodoLectivoController::class, 'getall'])
        ->middleware('check.permissions:usuarios.alumnos.ver');

    // Listado de grados para select
    Route::get('/grados/list', [ConfigGradoController::class, 'getall'])
        ->middleware('check.permissions:usuarios.alumnos.ver');

    // Listado de grupos para select (con filtros opcionales)
    Route::get('/grupos/list', [ConfigGruposController::class, 'getall'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::get('/grupos/by-periodo/{periodoId}', [ConfigGruposController::class, 'byPeriodoLectivo'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::get('/grupos/by-grado/{gradoId}', [ConfigGruposController::class, 'byGrado'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::get('/grupos/by-turno/{turnoId}', [ConfigGruposController::class, 'byTurno'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
    Route::get('/grupos/filtered', [ConfigGruposController::class, 'byAllFilters'])
        ->middleware('check.permissions:usuarios.alumnos.ver'); // Acepta query params: periodo_id, grado_id, turno_id

    // Listado de turnos para select
    Route::get('/turnos/list', [ConfigTurnosController::class, 'getall'])
        ->middleware('check.permissions:usuarios.alumnos.ver');
});
