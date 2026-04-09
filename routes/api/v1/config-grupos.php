<?php

use App\Http\Controllers\Api\V1\ConfigGruposController;
use Illuminate\Support\Facades\Route;

Route::prefix('config-grupos')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfigGruposController::class, 'index'])
        ->middleware('check.permissions:config_grupos.index');

    // Ruta para obtener grupos activos del docente (requiere permiso específico)
    // DEBE IR ANTES DE LOS WILDCARDS /{id}
    Route::get('/my-active-groups', [ConfigGruposController::class, 'myActiveGroups'])
        ->middleware('check.permissions:operaciones.docentes');

    Route::get('/getall', [ConfigGruposController::class, 'getall'])
        ->middleware('check.permissions:config_grupos.index');

    // Ruta de filtros múltiples - DEBE IR ANTES de /{id}
    Route::get('/filtered', [ConfigGruposController::class, 'byAllFilters'])
        ->middleware('check.permissions:config_grupos.filter');

    Route::post('/', [ConfigGruposController::class, 'store'])
        ->middleware('check.permissions:config_grupos.create');

    Route::get('/{id}', [ConfigGruposController::class, 'show'])
        ->middleware('check.permissions:config_grupos.show');

    Route::put('/{id}', [ConfigGruposController::class, 'update'])
        ->middleware('check.permissions:config_grupos.update');

    Route::delete('/{id}', [ConfigGruposController::class, 'destroy'])
        ->middleware('check.permissions:config_grupos.delete');

    // Rutas para obtener listas de opciones
    Route::get('/opciones/grados', [ConfigGruposController::class, 'getGrados'])
        ->middleware('check.permissions:config_grupos.index');

    Route::get('/opciones/secciones', [ConfigGruposController::class, 'getSecciones'])
        ->middleware('check.permissions:config_grupos.index');

    Route::get('/opciones/docentes-guia', [ConfigGruposController::class, 'getDocentesGuia'])
        ->middleware('check.permissions:config_grupos.index');


    Route::get('/opciones/turnos', [ConfigGruposController::class, 'getTurnos'])
        ->middleware('check.permissions:config_grupos.index');

    Route::get('/opciones/periodos-lectivos', [ConfigGruposController::class, 'getPeriodosLectivos'])
        ->middleware('check.permissions:config_grupos.index');

    // Rutas de filtros específicos
    Route::get('/by-grado/{gradoId}', [ConfigGruposController::class, 'getByGrado'])
        ->middleware('check.permissions:config_grupos.filter');

    Route::get('/by-seccion/{seccionId}', [ConfigGruposController::class, 'getBySeccion'])
        ->middleware('check.permissions:config_grupos.filter');

    Route::get('/by-turno/{turnoId}', [ConfigGruposController::class, 'getByTurno'])
        ->middleware('check.permissions:config_grupos.filter');

    // Ruta por modalidad removida

    Route::get('/by-docente-guia/{docenteId}', [ConfigGruposController::class, 'byDocenteGuia'])
        ->middleware('check.permissions:config_grupos.filter');

    Route::get('/by-periodo-lectivo/{periodoLectivoId}', [ConfigGruposController::class, 'byPeriodoLectivo'])
        ->middleware('check.permissions:config_grupos.filter');



    // Rutas para sincronización (modo offline)
    Route::prefix('sync')->group(function () {
        Route::get('/unsynced', [ConfigGruposController::class, 'unsynced'])
            ->middleware('check.permissions:config_grupos.sync');

        Route::patch('/{id}/mark-synced', [ConfigGruposController::class, 'markSynced'])
            ->middleware('check.permissions:config_grupos.sync');

        Route::get('/updated-after', [ConfigGruposController::class, 'updatedAfter'])
            ->middleware('check.permissions:config_grupos.sync');
    });
});
