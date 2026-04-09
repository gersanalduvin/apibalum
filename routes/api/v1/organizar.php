<?php

use App\Http\Controllers\Api\V1\OrganizarListasController;
use Illuminate\Support\Facades\Route;

Route::prefix('organizar')->group(function () {
    Route::get('/catalogos', [OrganizarListasController::class, 'catalogos'])->middleware('check.permissions:organizar.lista');
    Route::get('/alumnos', [OrganizarListasController::class, 'alumnos'])->middleware('check.permissions:organizar.lista');
    Route::get('/alumnos/pdf', [OrganizarListasController::class, 'alumnosPdf'])->middleware('check.permissions:organizar.lista');
    Route::get('/alumnos/excel', [OrganizarListasController::class, 'alumnosExcel'])->middleware('check.permissions:organizar.lista');
    Route::get('/grupos', [OrganizarListasController::class, 'grupos'])->middleware('check.permissions:organizar.lista');
    Route::post('/asignar-grupo', [OrganizarListasController::class, 'asignarGrupo'])->middleware('check.permissions:organizar.lista');
});