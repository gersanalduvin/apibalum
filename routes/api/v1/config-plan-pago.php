<?php

use App\Http\Controllers\Api\V1\ConfigPlanPagoController;
use App\Http\Controllers\Api\V1\ConfigPlanPagoDetalleController;
use Illuminate\Support\Facades\Route;

// Rutas para Config Plan Pago
Route::prefix('config-plan-pago')->group(function () {
    // Rutas básicas CRUD
    Route::get('/', [ConfigPlanPagoController::class, 'index'])->middleware('check.permissions:config_plan_pagos.index');
    Route::post('/', [ConfigPlanPagoController::class, 'store'])->middleware('check.permissions:config_plan_pagos.store');
    Route::get('/{id}', [ConfigPlanPagoController::class, 'show'])->middleware('check.permissions:config_plan_pagos.show');
    Route::put('/{id}', [ConfigPlanPagoController::class, 'update'])->middleware('check.permissions:config_plan_pagos.update');
    Route::delete('/{id}', [ConfigPlanPagoController::class, 'destroy'])->middleware('check.permissions:config_plan_pagos.destroy');

    // Rutas adicionales
    Route::get('/getall/all', [ConfigPlanPagoController::class, 'getall'])->middleware('check.permissions:config_plan_pagos.getall');
    Route::get('/search/query', [ConfigPlanPagoController::class, 'search'])->middleware('check.permissions:config_plan_pagos.search');
    Route::get('/inactive/all', [ConfigPlanPagoController::class, 'getAllInactive'])->middleware('check.permissions:config_plan_pagos.inactive');
    Route::get('/periodo-lectivo/{periodoLectivoId}', [ConfigPlanPagoController::class, 'getByPeriodoLectivo'])->middleware('check.permissions:config_plan_pagos.by_periodo');
    Route::get('/periodos-lectivos/all', [ConfigPlanPagoController::class, 'getPeriodosLectivos'])->middleware('check.permissions:config_plan_pagos.index');
    Route::get('/catalogo-cuentas/all', [ConfigPlanPagoController::class, 'getCatalogoCuentas'])->middleware('check.permissions:config_plan_pagos.index');

    // Rutas para cambio de estado
    Route::patch('/{id}/toggle-status', [ConfigPlanPagoController::class, 'toggleStatus'])->middleware('check.permissions:config_plan_pagos.toggle_status');
    Route::patch('/{id}/activate', [ConfigPlanPagoController::class, 'activate'])->middleware('check.permissions:config_plan_pagos.activate');
    Route::patch('/{id}/deactivate', [ConfigPlanPagoController::class, 'deactivate'])->middleware('check.permissions:config_plan_pagos.deactivate');
});

// Rutas para Config Plan Pago Detalle
Route::prefix('config-plan-pago-detalle')->group(function () {
    // Rutas básicas CRUD
    Route::get('/', [ConfigPlanPagoDetalleController::class, 'index'])->middleware('check.permissions:config_plan_pagos_detalle.index');
    Route::post('/', [ConfigPlanPagoDetalleController::class, 'store'])->middleware('check.permissions:config_plan_pagos_detalle.store');
    Route::get('/{id}', [ConfigPlanPagoDetalleController::class, 'show'])->middleware('check.permissions:config_plan_pagos_detalle.show');
    Route::put('/{id}', [ConfigPlanPagoDetalleController::class, 'update'])->middleware('check.permissions:config_plan_pagos_detalle.update');
    Route::delete('/{id}', [ConfigPlanPagoDetalleController::class, 'destroy'])->middleware('check.permissions:config_plan_pagos_detalle.destroy');

    // Rutas adicionales
    Route::get('/getall/all', [ConfigPlanPagoDetalleController::class, 'getall'])->middleware('check.permissions:config_plan_pagos_detalle.getall');
    Route::get('/search/query', [ConfigPlanPagoDetalleController::class, 'search'])->middleware('check.permissions:config_plan_pagos_detalle.search');

    // Rutas de filtrado específico
    Route::get('/plan-pago/{planPagoId}', [ConfigPlanPagoDetalleController::class, 'getByPlanPago'])->middleware('check.permissions:config_plan_pagos_detalle.by_plan');
    Route::get('/colegiaturas/all', [ConfigPlanPagoDetalleController::class, 'getColegiaturas'])->middleware('check.permissions:config_plan_pagos_detalle.colegiaturas');
    Route::get('/mes/{mes}', [ConfigPlanPagoDetalleController::class, 'getByMes'])->middleware('check.permissions:config_plan_pagos_detalle.by_mes');
    Route::get('/moneda/{moneda}', [ConfigPlanPagoDetalleController::class, 'getByMoneda'])->middleware('check.permissions:config_plan_pagos_detalle.by_moneda');

    // Rutas de utilidad
    Route::post('/{id}/duplicate', [ConfigPlanPagoDetalleController::class, 'duplicate'])->middleware('check.permissions:config_plan_pagos_detalle.duplicate');
    Route::get('/check/codigo', [ConfigPlanPagoDetalleController::class, 'checkCodigo'])->middleware('check.permissions:config_plan_pagos_detalle.check_codigo');
    Route::get('/check/nombre', [ConfigPlanPagoDetalleController::class, 'checkNombre'])->middleware('check.permissions:config_plan_pagos_detalle.check_nombre');
});
