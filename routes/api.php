<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('/user', [App\Http\Controllers\AuthController::class, 'user'])->middleware('auth:sanctum');

// Auth Routes (no authentication required)
require __DIR__ . '/api/v1/auth.php';

// API V1 Routes (authentication required)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('reportes/carga-academica')->group(function () {
        require __DIR__ . '/api/v1/reportes_carga_academica.php';
    });
    // Rutas de usuarios por tipo (nueva estructura)
    require __DIR__ . '/api/v1/usuarios-administrativos.php';
    require __DIR__ . '/api/v1/usuarios-docentes.php';
    require __DIR__ . '/api/v1/usuarios-docentes-asignaciones.php';
    require __DIR__ . '/api/v1/usuarios-alumnos.php';
    require __DIR__ . '/api/v1/usuarios-familias.php';

    // Rutas de usuarios generales (mantener para compatibilidad)
    require __DIR__ . '/api/v1/users.php';
    require __DIR__ . '/api/v1/users_grupos.php';
    require __DIR__ . '/api/v1/roles.php';
    require __DIR__ . '/api/v1/permissions.php';
    require __DIR__ . '/api/v1/conf_periodo_lectivo.php';

    // Configuración de módulos académicos
    require __DIR__ . '/api/v1/config-modalidad.php';
    require __DIR__ . '/api/v1/config-grado.php';
    require __DIR__ . '/api/v1/config-seccion.php';
    require __DIR__ . '/api/v1/config-turnos.php';
    require __DIR__ . '/api/v1/config-grupos.php';
    require __DIR__ . '/api/v1/config-catalogo-cuentas.php';
    require __DIR__ . '/api/v1/config-parametros.php';
    require __DIR__ . '/api/v1/config-formas-pago.php';
    require __DIR__ . '/api/v1/config-aranceles.php';
    require __DIR__ . '/api/v1/config-plan-pago.php';
    require __DIR__ . '/api/v1/config-not-escala.php';
    require __DIR__ . '/api/v1/config-not-semestre.php';
    require __DIR__ . '/api/v1/not-asignatura-grado.php';
    require __DIR__ . '/api/v1/not-materias-areas.php';
    require __DIR__ . '/api/v1/not-materias.php';
    require __DIR__ . '/api/v1/asistencias.php';
    require __DIR__ . '/api/v1/academico_permisos.php';

    // Módulo de aranceles de usuarios
    require __DIR__ . '/api/v1/users_aranceles.php';

    // Módulo de recibos
    require __DIR__ . '/api/v1/recibos.php';
    require __DIR__ . '/api/v1/buscar_recibos.php';

    // Módulo de reportes
    require __DIR__ . '/api/v1/reporte_matricula.php';
    require __DIR__ . '/api/v1/reporte_nuevo_ingreso.php';
    require __DIR__ . '/api/v1/reporte_cierre_caja.php';
    require __DIR__ . '/api/v1/reporte_cuenta_x_cobrar.php';
    require __DIR__ . '/api/v1/reporte_arqueo_caja.php';
    require __DIR__ . '/api/v1/reportes_retirados.php';
    require __DIR__ . '/api/v1/reportes_notas.php';
    require __DIR__ . '/api/v1/boletin-escolar.php';
    require __DIR__ . '/api/v1/reportes_actividades.php';

    // Auditoría
    require __DIR__ . '/api/v1/audits.php';
    require __DIR__ . '/api/v1/login_logs.php';

    // Arqueo
    require __DIR__ . '/api/v1/config-arqueo-moneda.php';
    require __DIR__ . '/api/v1/config-arqueo.php';
    require __DIR__ . '/api/v1/config-arqueo-detalle.php';

    // Módulo de inventario
    require __DIR__ . '/api/v1/productos.php';
    require __DIR__ . '/api/v1/categorias.php';
    require __DIR__ . '/api/v1/inventario-categorias.php';
    require __DIR__ . '/api/v1/movimientos-inventario.php';
    require __DIR__ . '/api/v1/reporte_utilidad_inventario.php';

    // Módulo Organizar Listas
    require __DIR__ . '/api/v1/organizar.php';
    // Módulo Listas por Grupo
    require __DIR__ . '/api/v1/listas_grupo.php';

    // Módulo de Mensajería
    require __DIR__ . '/api/v1/mensajes.php';

    // Módulo Generador de Horarios
    require __DIR__ . '/api/v1/schedule.php';
    require __DIR__ . '/api/v1/schedule_asignaturas.php';

    // Módulo de Agenda
    require __DIR__ . '/api/v1/agenda.php';
    require __DIR__ . '/api/v1/lesson-plans.php';

    // Módulo de Avisos
    Route::prefix('avisos')->group(function () {
        require __DIR__ . '/api/v1/avisos.php';
    });

    // Rutas de marcas eliminadas
    require __DIR__ . '/api/v1/calificaciones.php';
    require __DIR__ . '/api/v1/academico-observaciones.php';
    require __DIR__ . '/api/v1/docente-dashboard.php';
});

require __DIR__ . '/auth.php';
