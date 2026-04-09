<?php

use App\Http\Controllers\Api\V1\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [RoleController::class, 'index'])->middleware('check.permissions:roles.ver');
    Route::get('/all', [RoleController::class, 'getAll'])->middleware('check.permissions:roles.ver');
    Route::post('/', [RoleController::class, 'store'])->middleware('check.permissions:roles.crear');
    Route::get('/{id}', [RoleController::class, 'show'])->middleware('check.permissions:roles.ver');
    Route::put('/{id}', [RoleController::class, 'update'])->middleware('check.permissions:roles.editar');
    Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('check.permissions:roles.eliminar');
    
    // Rutas adicionales
    Route::get('/search/query', [RoleController::class, 'search'])->middleware('check.permissions:roles.ver');
    Route::post('/by-permissions', [RoleController::class, 'byPermissions'])->middleware('check.permissions:roles.ver');
    Route::post('/{id}/restore', [RoleController::class, 'restore'])->middleware('check.permissions:roles.editar');
    Route::get('/permissions/available', [RoleController::class, 'availablePermissions'])->middleware('check.permissions:permisos.ver');
});