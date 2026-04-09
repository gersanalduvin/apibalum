<?php

use App\Http\Controllers\Api\V1\AcademicoPermisosController;
use Illuminate\Support\Facades\Route;

Route::prefix('academico/permisos')->group(function () {
    Route::get('/', [AcademicoPermisosController::class, 'index'])
        ->middleware('check.permissions:configuracion_academica.permisos.ver');

    Route::post('/', [AcademicoPermisosController::class, 'store'])
        ->middleware('check.permissions:configuracion_academica.permisos.editar');
});
