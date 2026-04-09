<?php

use App\Http\Controllers\Api\V1\CategoriaController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventario-categorias')->middleware(['auth:sanctum', 'check.permissions'])->group(function () {

    // Rutas CRUD básicas
    Route::get('/', [CategoriaController::class, 'index'])->name('inventario_categorias.index');
    Route::post('/', [CategoriaController::class, 'store'])->name('inventario_categorias.store');
    Route::get('/{id}', [CategoriaController::class, 'show'])->name('inventario_categorias.show');
    Route::put('/{id}', [CategoriaController::class, 'update'])->name('inventario_categorias.update');
    Route::delete('/{id}', [CategoriaController::class, 'destroy'])->name('inventario_categorias.destroy');

    // Ruta para obtener todas las categorías sin paginación
    Route::get('/all/list', [CategoriaController::class, 'getall'])->name('inventario_categorias.getall');

    // Rutas específicas para funcionalidades avanzadas
    Route::get('/tree/hierarchy', [CategoriaController::class, 'tree'])->name('inventario_categorias.tree');
    Route::get('/roots/list', [CategoriaController::class, 'roots'])->name('inventario_categorias.roots');
    Route::get('/{categoriaId}/children', [CategoriaController::class, 'children'])->name('inventario_categorias.children');
    Route::get('/active/list', [CategoriaController::class, 'active'])->name('inventario_categorias.active');

    // Rutas para búsqueda y filtros
    Route::get('/search/query', [CategoriaController::class, 'search'])->name('inventario_categorias.search');

    // Rutas para gestión de estado
    Route::patch('/{id}/toggle-status', [CategoriaController::class, 'toggleStatus'])->name('inventario_categorias.toggle-status');

    // Rutas para estadísticas y reportes
    Route::get('/statistics/summary', [CategoriaController::class, 'statistics'])->name('inventario_categorias.statistics');

    // Rutas para sincronización (modo offline)
    Route::post('/sync/data', [CategoriaController::class, 'sync'])->name('inventario_categorias.sync');
});
