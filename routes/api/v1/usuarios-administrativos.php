<?php

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('usuarios/administrativos')->group(function () {
    // Ruta para cambio de contraseña - solo requiere autenticación
    Route::put('/change-password', [UserController::class, 'changePassword']);

    // Rutas para manejo de fotos (deben ir antes de las rutas con {id})
    Route::post('/{id}/upload-photo', [UserController::class, 'uploadPhoto'])
        ->middleware('check.permissions:usuarios.administrativos.editar');
    Route::delete('/{id}/delete-photo', [UserController::class, 'deletePhoto'])
        ->middleware('check.permissions:usuarios.administrativos.editar');

    // Rutas específicas para usuarios administrativos
    Route::get('/export', [UserController::class, 'exportAdministrativos'])
        ->middleware('check.permissions:usuarios.administrativos.exportar');
    Route::post('/import', [UserController::class, 'importAdministrativos'])
        ->middleware('check.permissions:usuarios.administrativos.importar');
    Route::put('/{id}/activate', [UserController::class, 'activate'])
        ->middleware('check.permissions:usuarios.administrativos.activar');
    Route::put('/{id}/deactivate', [UserController::class, 'deactivate'])
        ->middleware('check.permissions:usuarios.administrativos.desactivar');
    Route::put('/{id}/change-password-admin', [UserController::class, 'changePasswordAdmin'])
        ->middleware('check.permissions:usuarios.administrativos.cambiar_password');

    // Generar contraseña aleatoria de 6 dígitos y enviarla por correo
    Route::post('/{id}/reset-password', [UserController::class, 'resetPasswordAdminAndSend'])
        ->middleware('check.permissions:usuarios.administrativos.cambiar_password');

    // ========================================
    // ENDPOINTS PARA CONTROLES SELECT
    // ========================================
    // Listado de roles existentes para select
    Route::get('/roles/list', [RoleController::class, 'getAll'])
        ->middleware('check.permissions:usuarios.administrativos.ver');

    // Rutas CRUD básicas
    Route::get('/', [UserController::class, 'indexAdministrativos'])
        ->middleware('check.permissions:usuarios.administrativos.ver');
    Route::post('/', [UserController::class, 'storeAdministrativo'])
        ->middleware('check.permissions:usuarios.administrativos.crear');
    Route::get('/{id}', [UserController::class, 'showAdministrativo'])
        ->middleware('check.permissions:usuarios.administrativos.ver');
    Route::put('/{id}', [UserController::class, 'updateAdministrativo'])
        ->middleware('check.permissions:usuarios.administrativos.editar');
    Route::delete('/{id}', [UserController::class, 'destroyAdministrativo'])
        ->middleware('check.permissions:usuarios.administrativos.eliminar');
});
