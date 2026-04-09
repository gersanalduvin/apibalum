<?php

use App\Http\Controllers\Api\V1\ConfPeriodoLectivoController;
use Illuminate\Support\Facades\Route;

Route::prefix('conf-periodo-lectivo')->group(function () {
    // Rutas CRUD básicas
    Route::get('/', [ConfPeriodoLectivoController::class, 'index'])
        ->middleware('check.permissions:conf_periodo_lectivo.index');
    
    Route::get('/getall', [ConfPeriodoLectivoController::class, 'getall'])
        ->middleware('check.permissions:conf_periodo_lectivo.index');
    
    Route::post('/', [ConfPeriodoLectivoController::class, 'store'])
        ->middleware('check.permissions:conf_periodo_lectivo.create');
    
    Route::get('/{id}', [ConfPeriodoLectivoController::class, 'show'])
        ->middleware('check.permissions:conf_periodo_lectivo.show');
    
    Route::put('/{id}', [ConfPeriodoLectivoController::class, 'update'])
        ->middleware('check.permissions:conf_periodo_lectivo.update');
    
    Route::delete('/{id}', [ConfPeriodoLectivoController::class, 'destroy'])
        ->middleware('check.permissions:conf_periodo_lectivo.delete');
    
    // Rutas de sincronización
    Route::prefix('sync')->group(function () {
        Route::get('/unsynced', [ConfPeriodoLectivoController::class, 'getUnsynced'])
            ->middleware('check.permissions:conf_periodo_lectivo.sync');
        
        Route::get('/updated-after', [ConfPeriodoLectivoController::class, 'getUpdatedAfter'])
            ->middleware('check.permissions:conf_periodo_lectivo.sync');
        
        Route::post('/mark-synced/{id}', [ConfPeriodoLectivoController::class, 'markAsSynced'])
            ->middleware('check.permissions:conf_periodo_lectivo.sync');
        
        Route::post('/from-client', [ConfPeriodoLectivoController::class, 'syncFromClient'])
            ->middleware('check.permissions:conf_periodo_lectivo.sync');
    });
});