<?php

use App\Http\Controllers\Api\V1\PermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('permissions')->group(function () {
    // Obtener todos los permisos
    Route::get('/', [PermissionController::class, 'index']);
    
    // Obtener permisos agrupados por módulo
    Route::get('/grouped', [PermissionController::class, 'grouped']);
    
    // Obtener permisos en formato plano
    Route::get('/flat', [PermissionController::class, 'flat']);
    
    // === RUTAS DE CATEGORÍAS ===
    // Obtener todas las categorías
    Route::get('/categories', [PermissionController::class, 'categories']);
    
    // Obtener permisos de una categoría específica
    Route::get('/category/{category}', [PermissionController::class, 'categoryPermissions']);
    
    // Obtener módulos de una categoría específica
    Route::get('/category/{category}/modules', [PermissionController::class, 'categoryModules']);
    
    // === RUTAS DE MÓDULOS ===
    // Obtener todos los módulos
    Route::get('/modules', [PermissionController::class, 'modules']);
    
    // Obtener permisos de un módulo específico
    Route::get('/module/{module}', [PermissionController::class, 'modulePermissions']);
    
    // === RUTAS DE ACCIONES ===
    // Obtener permisos por tipo de acción
    Route::get('/action/{action}', [PermissionController::class, 'byAction']);
    
    // === RUTAS DE VALIDACIÓN ===
    // Validar lista de permisos
    Route::post('/validate', [PermissionController::class, 'validatePermissions']);
    
    // Verificar si existe un permiso específico
    Route::post('/exists', [PermissionController::class, 'exists']);
    
    // === RUTAS DE UTILIDAD ===
    // Listados detallados
    Route::get('/detailed', [PermissionController::class, 'detailed']);
    Route::get('/flat-detailed', [PermissionController::class, 'flatDetailed']);

    // Generar datos para seeder
    Route::get('/seeder-data', [PermissionController::class, 'seederData']);
});