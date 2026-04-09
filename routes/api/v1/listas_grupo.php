<?php

use App\Http\Controllers\Api\V1\ListasGrupoController;
use Illuminate\Support\Facades\Route;

Route::prefix('listas-grupo')->group(function () {
    Route::get('/catalogos', [ListasGrupoController::class, 'catalogos'])->middleware('check.permissions:ver_listas_grupo');
    Route::get('/alumnos', [ListasGrupoController::class, 'alumnos'])->middleware('check.permissions:ver_listas_grupo');
    Route::get('/alumnos/pdf', [ListasGrupoController::class, 'alumnosPdf'])->middleware('check.permissions:ver_listas_grupo');
    Route::get('/alumnos/excel', [ListasGrupoController::class, 'alumnosExcel'])->middleware('check.permissions:ver_listas_grupo');
});