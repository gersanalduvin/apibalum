<?php

use App\Http\Controllers\Api\V1\CategoriaController;
use Illuminate\Support\Facades\Route;

Route::prefix('categorias')->middleware(['auth:sanctum', 'check.permissions'])->group(function () {
    
    // Rutas CRUD básicas
    Route::get('/', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::post('/', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::get('/{id}', [CategoriaController::class, 'show'])->name('categorias.show');
    Route::put('/{id}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/{id}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
    
    // Ruta para obtener todas las categorías sin paginación
    Route::get('/all/list', [CategoriaController::class, 'getall'])->name('categorias.getall');
    
    // Rutas específicas para funcionalidades avanzadas
    Route::get('/tree/hierarchy', [CategoriaController::class, 'tree'])->name('categorias.tree');
    Route::get('/roots/list', [CategoriaController::class, 'roots'])->name('categorias.roots');
    Route::get('/{categoriaId}/children', [CategoriaController::class, 'children'])->name('categorias.children');
    Route::get('/active/list', [CategoriaController::class, 'active'])->name('categorias.active');
    
    // Rutas para búsqueda y filtros
    Route::get('/search/query', [CategoriaController::class, 'search'])->name('categorias.search');
    
    // Rutas para gestión de estado
    Route::patch('/{id}/toggle-status', [CategoriaController::class, 'toggleStatus'])->name('categorias.toggle-status');
    
    // Rutas para estadísticas y reportes
    Route::get('/statistics/summary', [CategoriaController::class, 'statistics'])->name('categorias.statistics');
    
    // Rutas para sincronización (modo offline)
    Route::post('/sync/data', [CategoriaController::class, 'sync'])->name('categorias.sync');
    
    // Endpoint de reordenamiento eliminado: el campo 'orden' fue retirado

});