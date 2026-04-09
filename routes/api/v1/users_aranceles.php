<?php

use App\Http\Controllers\Api\V1\UsersArancelesController;
use Illuminate\Support\Facades\Route;

Route::prefix('users-aranceles')->name('users-aranceles.')->group(function () {

    // Rutas principales CRUD
    Route::get('/', [UsersArancelesController::class, 'index'])
        ->middleware('check.permissions:users_aranceles.index')
        ->name('index');

    Route::get('/getall', [UsersArancelesController::class, 'getAll'])
        ->middleware('check.permissions:users_aranceles.getall')
        ->name('getall');

    Route::post('/', [UsersArancelesController::class, 'store'])
        ->middleware('check.permissions:users_aranceles.store')
        ->name('store');

    // Endpoints específicos requeridos (ANTES de las rutas con parámetros)

    // 3. Anular recargos
    Route::patch('/anular-recargo', [UsersArancelesController::class, 'anularRecargo'])
        ->middleware('check.permissions:users_aranceles.anular_recargo')
        ->name('anular-recargo');

    // 4. Exonerar
    Route::patch('/exonerar', [UsersArancelesController::class, 'exonerar'])
        ->middleware('check.permissions:users_aranceles.exonerar')
        ->name('exonerar');

    // 7. Aplicar Beca
    Route::patch('/aplicar-beca', [UsersArancelesController::class, 'aplicarBeca'])
        ->middleware('check.permissions:users_aranceles.aplicar_beca')
        ->name('aplicar-beca');

    // 8. Aplicar Descuento
    Route::patch('/aplicar-descuento', [UsersArancelesController::class, 'aplicarDescuento'])
        ->middleware('check.permissions:users_aranceles.aplicar_descuento')
        ->name('aplicar-descuento');

    // 5. Aplicar plan de pago
    Route::post('/aplicar-plan-pago', [UsersArancelesController::class, 'aplicarPlanPago'])
        ->middleware('check.permissions:users_aranceles.aplicar_plan_pago')
        ->name('aplicar-plan-pago');

    // 6. Aplicar pago
    Route::patch('/aplicar-pago', [UsersArancelesController::class, 'aplicarPago'])
        ->middleware('check.permissions:users_aranceles.aplicar_pago')
        ->name('aplicar-pago');

    // 9. Revertir pago
    Route::patch('/{id}/revertir', [UsersArancelesController::class, 'revertir'])
        ->middleware('check.permissions:users_aranceles.revertir')
        ->name('revertir');

    // Rutas adicionales para consultas específicas

    // Obtener aranceles por usuario
    Route::get('/usuario/{userId}', [UsersArancelesController::class, 'getByUser'])
        ->middleware('check.permissions:users_aranceles.by_user')
        ->name('by-user');

    // Generar reporte PDF por usuario
    Route::get('/usuario/{userId}/reporte-pdf', [UsersArancelesController::class, 'generarPdfReporte'])
        ->middleware('check.permissions:users_aranceles.reporte_pdf')
        ->name('reporte-pdf');

    // Obtener aranceles pendientes por usuario
    Route::get('/usuario/{userId}/pendientes', [UsersArancelesController::class, 'getPendientesByUser'])
        ->middleware('check.permissions:users_aranceles.pendientes_by_user')
        ->name('pendientes-by-user');

    // Obtener aranceles con recargo
    Route::get('/reportes/con-recargo', [UsersArancelesController::class, 'getConRecargo'])
        ->middleware('check.permissions:users_aranceles.con_recargo')
        ->name('con-recargo');

    // Obtener aranceles con saldo pendiente
    Route::get('/reportes/con-saldo-pendiente', [UsersArancelesController::class, 'getConSaldoPendiente'])
        ->middleware('check.permissions:users_aranceles.con_saldo_pendiente')
        ->name('con-saldo-pendiente');

    // Obtener estadísticas
    Route::get('/reportes/estadisticas', [UsersArancelesController::class, 'getEstadisticas'])
        ->middleware('check.permissions:users_aranceles.estadisticas')
        ->name('estadisticas');

    // Nuevos endpoints agregados

    // Obtener períodos lectivos
    Route::get('/periodos-lectivos', [UsersArancelesController::class, 'getPeriodosLectivos'])
        ->middleware('check.permissions:users_aranceles.periodos_lectivos')
        ->name('periodos-lectivos');

    // Obtener planes de pago por período lectivo
    Route::get('/planes-pago/periodo/{periodoLectivoId}', [UsersArancelesController::class, 'getPlanesPagoPorPeriodo'])
        ->middleware('check.permissions:users_aranceles.planes_pago_por_periodo')
        ->name('planes-pago-por-periodo');

    // Rutas CRUD con parámetros (AL FINAL para evitar conflictos)
    Route::get('/{id}', [UsersArancelesController::class, 'show'])
        ->middleware('check.permissions:users_aranceles.show')
        ->name('show');

    Route::put('/{id}', [UsersArancelesController::class, 'update'])
        ->middleware('check.permissions:users_aranceles.update')
        ->name('update');

    Route::delete('/{id}', [UsersArancelesController::class, 'destroy'])
        ->middleware('check.permissions:users_aranceles.destroy')
        ->name('destroy');
});
