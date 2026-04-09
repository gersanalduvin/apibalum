<?php

use App\Http\Controllers\Api\V1\ReciboController;
use Illuminate\Support\Facades\Route;

Route::prefix('recibos')->group(function () {
    Route::get('/', [ReciboController::class, 'index'])->middleware('check.permissions:recibos.index');
    Route::post('/', [ReciboController::class, 'store'])->middleware('check.permissions:recibos.store');
    Route::delete('/{id}', [ReciboController::class, 'destroy'])->middleware('check.permissions:recibos.destroy');
    Route::put('/{id}/anular', [ReciboController::class, 'anular'])->middleware('check.permissions:recibos.anular');
    Route::get('/{id}/pdf', [ReciboController::class, 'imprimirPdf'])->middleware('check.permissions:recibos.imprimir');
    Route::get('/{id}/reporte', [ReciboController::class, 'reporte'])->middleware('check.permissions:recibos.reporte');
    Route::get('/alumnos/search', [ReciboController::class, 'buscarAlumnos'])->middleware('check.permissions:recibos.index');
    Route::get('/catalogos/productos', [ReciboController::class, 'catalogoProductos'])->middleware('check.permissions:recibos.index');
    Route::get('/catalogos/aranceles', [ReciboController::class, 'catalogoAranceles'])->middleware('check.permissions:recibos.index');
    Route::get('/catalogos/formas-pago', [ReciboController::class, 'catalogoFormasPago'])->middleware('check.permissions:recibos.index');
    Route::get('/catalogos/parametros-caja', [ReciboController::class, 'parametrosCaja'])->middleware('check.permissions:recibos.index');
    Route::get('/catalogos/periodos-planes-pago', [ReciboController::class, 'periodoPlanesPago'])->middleware('check.permissions:recibos.index');
    Route::post('/alumnos', [ReciboController::class, 'crearAlumnoConPlan'])->middleware('check.permissions:recibos.store');
    Route::get('/usuario/{userId}/historial-pdf', [ReciboController::class, 'imprimirHistorialPdf'])->middleware('check.permissions:recibos.historial_pdf');
});
