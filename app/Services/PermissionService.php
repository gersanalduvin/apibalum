<?php

namespace App\Services;

class PermissionService
{
    /**
     * Definición de todos los permisos de la aplicación
     * Estructura: categoría => módulo => [acciones]
     */
    private const PERMISSIONS = [
        'configuracion' => [
            'roles' => [
                'ver' => 'roles.ver',
                'crear' => 'roles.crear',
                'editar' => 'roles.editar',
                'eliminar' => 'roles.eliminar'
            ],
            'permisos' => [
                'ver' => 'permisos.ver',

            ],
            'config_catalogo_cuentas' => [
                'ver' => 'config_catalogo_cuentas.index',
                'mostrar' => 'config_catalogo_cuentas.show',
                'crear' => 'config_catalogo_cuentas.create',
                'editar' => 'config_catalogo_cuentas.update',
                'eliminar' => 'config_catalogo_cuentas.delete',
                'sincronizar' => 'config_catalogo_cuentas.sync',
                'filtrar' => 'config_catalogo_cuentas.filter',
            ],
            'config_formas_pago' => [
                'ver' => 'config_formas_pago.index',
                'mostrar' => 'config_formas_pago.show',
                'crear' => 'config_formas_pago.create',
                'editar' => 'config_formas_pago.update',
                'eliminar' => 'config_formas_pago.delete',
                'sincronizar' => 'config_formas_pago.sync',
                'buscar' => 'config_formas_pago.search',
            ],
            'config_aranceles' => [
                'ver' => 'config_aranceles.index',
                'mostrar' => 'config_aranceles.show',
                'crear' => 'config_aranceles.create',
                'editar' => 'config_aranceles.update',
                'eliminar' => 'config_aranceles.delete',
                'sincronizar' => 'config_aranceles.sync',
                'buscar' => 'config_aranceles.search',
            ],
            'config_plan_pago' => [
                'index' => 'config_plan_pagos.index',
                'show' => 'config_plan_pagos.show',
                'store' => 'config_plan_pagos.store',
                'update' => 'config_plan_pagos.update',
                'destroy' => 'config_plan_pagos.destroy',
                'getall' => 'config_plan_pagos.getall',
                'search' => 'config_plan_pagos.search',
                'inactive' => 'config_plan_pagos.inactive',
                'by_periodo' => 'config_plan_pagos.by_periodo',
                'toggle_status' => 'config_plan_pagos.toggle_status',
                'activate' => 'config_plan_pagos.activate',
                'deactivate' => 'config_plan_pagos.deactivate',
            ],
            'config_plan_pago_detalle' => [
                'index' => 'config_plan_pagos_detalle.index',
                'show' => 'config_plan_pagos_detalle.show',
                'store' => 'config_plan_pagos_detalle.store',
                'update' => 'config_plan_pagos_detalle.update',
                'destroy' => 'config_plan_pagos_detalle.destroy',
                'getall' => 'config_plan_pagos_detalle.getall',
                'search' => 'config_plan_pagos_detalle.search',
                'by_plan' => 'config_plan_pagos_detalle.by_plan',
                'colegiaturas' => 'config_plan_pagos_detalle.colegiaturas',
                'by_mes' => 'config_plan_pagos_detalle.by_mes',
                'by_moneda' => 'config_plan_pagos_detalle.by_moneda',
                'duplicate' => 'config_plan_pagos_detalle.duplicate',
                'check_codigo' => 'config_plan_pagos_detalle.check_codigo',
                'check_nombre' => 'config_plan_pagos_detalle.check_nombre',
            ],

        ],
        'usuarios' => [
            'administrativos' => [
                'ver' => 'usuarios.administrativos.ver',
                'crear' => 'usuarios.administrativos.crear',
                'editar' => 'usuarios.administrativos.editar',
                'eliminar' => 'usuarios.administrativos.eliminar',
                'activar' => 'usuarios.administrativos.activar',
                'desactivar' => 'usuarios.administrativos.desactivar',
                'cambiar_password' => 'usuarios.administrativos.cambiar_password',
                'exportar' => 'usuarios.administrativos.exportar',
                'importar' => 'usuarios.administrativos.importar',
            ],
            'docentes' => [
                'ver' => 'usuarios.docentes.ver',
                'crear' => 'usuarios.docentes.crear',
                'editar' => 'usuarios.docentes.editar',
                'eliminar' => 'usuarios.docentes.eliminar',
                'activar' => 'usuarios.docentes.activar',
                'desactivar' => 'usuarios.docentes.desactivar',
                'cambiar_password' => 'usuarios.docentes.cambiar_password',
                'asignar_materias' => 'usuarios.docentes.asignar_materias',
                'ver_horarios' => 'usuarios.docentes.ver_horarios',
                'exportar' => 'usuarios.docentes.exportar',
                'importar' => 'usuarios.docentes.importar',
                'operaciones' => 'operaciones.docentes', // Legacy
            ],
            'alumnos' => [
                'ver' => 'usuarios.alumnos.ver',
                'crear' => 'usuarios.alumnos.crear',
                'editar' => 'usuarios.alumnos.editar',
                'eliminar' => 'usuarios.alumnos.eliminar',
                'activar' => 'usuarios.alumnos.activar',
                'desactivar' => 'usuarios.alumnos.desactivar',
                'cambiar_password' => 'usuarios.alumnos.cambiar_password',
                'ver_expediente' => 'usuarios.alumnos.ver_expediente',
                'editar_expediente' => 'usuarios.alumnos.editar_expediente',
                'ver_notas' => 'usuarios.alumnos.ver_notas',
                'matricular' => 'usuarios.alumnos.matricular',
                'trasladar' => 'usuarios.alumnos.trasladar',
                'retirar' => 'usuarios.alumnos.retirar',
                'retirados' => 'usuarios.alumnos.retirados',
                'exportar' => 'usuarios.alumnos.exportar',
                'importar' => 'usuarios.alumnos.importar',
                'subir_foto' => 'usuarios.alumnos.subir_foto',
                'eliminar_foto' => 'usuarios.alumnos.eliminar_foto',
            ],
            'familias' => [
                'ver' => 'usuarios.familias.ver',
                'crear' => 'usuarios.familias.crear',
                'editar' => 'usuarios.familias.editar',
                'eliminar' => 'usuarios.familias.eliminar',
                'activar' => 'usuarios.familias.activar',
                'desactivar' => 'usuarios.familias.desactivar',
                'cambiar_password' => 'usuarios.familias.cambiar_password',
                'vincular_estudiante' => 'usuarios.familias.vincular_estudiante',
                'desvincular_estudiante' => 'usuarios.familias.desvincular_estudiante',
                'ver_estudiantes' => 'usuarios.familias.ver_estudiantes',
                'exportar' => 'usuarios.familias.exportar',
                'importar' => 'usuarios.familias.importar',
                'envio_masivo' => 'usuarios.familias.envio_masivo',
            ],
            'generales' => [
                'ver' => 'usuarios.ver',
                'crear' => 'usuarios.crear',
                'editar' => 'usuarios.editar',
                'eliminar' => 'usuarios.eliminar',
                'gestionar' => 'usuarios.gestionar',
            ],
        ],
        'aranceles' => [
            'users_aranceles' => [
                'index' => 'users_aranceles.index',
                'getall' => 'users_aranceles.getall',
                'store' => 'users_aranceles.store',
                'show' => 'users_aranceles.show',
                'update' => 'users_aranceles.update',
                'destroy' => 'users_aranceles.destroy',
                'anular_recargo' => 'users_aranceles.anular_recargo',
                'exonerar' => 'users_aranceles.exonerar',
                'aplicar_plan_pago' => 'users_aranceles.aplicar_plan_pago',
                'aplicar_pago' => 'users_aranceles.aplicar_pago',
                'by_user' => 'users_aranceles.by_user',
                'pendientes_by_user' => 'users_aranceles.pendientes_by_user',
                'con_recargo' => 'users_aranceles.con_recargo',
                'con_saldo_pendiente' => 'users_aranceles.con_saldo_pendiente',
                'estadisticas' => 'users_aranceles.estadisticas',
                'periodos_lectivos' => 'users_aranceles.periodos_lectivos',
                'planes_pago_por_periodo' => 'users_aranceles.planes_pago_por_periodo',
                'aplicar_beca' => 'users_aranceles.aplicar_beca',
                'aplicar_descuento' => 'users_aranceles.aplicar_descuento',
                'reporte_pdf' => 'users_aranceles.reporte_pdf',
                'revertir' => 'users_aranceles.revertir',
            ],
            'recibos' => [
                'index' => 'recibos.index',
                'store' => 'recibos.store',
                'destroy' => 'recibos.destroy',
                'anular' => 'recibos.anular',
                'eliminar_anulado' => 'recibos.eliminar_anulado',
                'anular_cualquier_fecha' => 'recibos.anular_cualquier_fecha',
                'imprimir' => 'recibos.imprimir',
                'reporte' => 'recibos.reporte',
                'historial_pdf' => 'recibos.historial_pdf',
                'buscar' => 'buscar_recibo', // Renamed from buscar_legacy for clarity
            ],
            'cobros_grupo' => [
                'index' => 'cobros_grupo.index',
            ],
        ],
        // Gestion category removed
        'reportes' => [
            'alumnos' => [
                'exportar' => 'exportar.alumnos',
            ],
            'ventas' => [],
            'usuarios_reportes' => [],
            'estadistica_matricula' => [
                'ver' => 'reportes.estadistica_matricula',
            ],
            'nuevo_ingreso' => [
                'ver' => 'repote.nuevoingreso'
            ],
            'generales' => [
                'ver' => 'reportes.ver',
            ],
            'cierre_caja' => [
                'ver' => 'reporte_cierre_caja.ver',
                'exportar_pdf' => 'reporte_cierre_caja.exportar_pdf',
                'exportar_excel' => 'reporte_cierre_caja.exportar_excel',
            ],
            'cuenta_x_cobrar' => [
                'ver' => 'reporte_cuenta_x_cobrar.ver',
                'exportar_pdf' => 'reporte_cuenta_x_cobrar.exportar_pdf'
            ],
            'carga_academica' => [
                'ver' => 'reportes.carga_academica.ver',
                'ver' => 'reportes.carga_academica.ver',
            ],
            'notas_por_asignatura' => [
                'ver' => 'notas.por.asignatura',
            ],
            'boletin_escolar' => [
                'generar' => 'generar.boletin',
            ],
            'consolidado_notas' => [
                'generar' => 'generar.consolidado_notas',
            ],
            'actividades_semana' => [
                'ver' => 'ver.actividades_semana',
            ],
            'utilidad_inventario' => [
                'ver' => 'inventario.reportes_utilidad.ver',
                'exportar' => 'inventario.reportes_utilidad.exportar',
            ],
        ],
        'organizacion' => [
            'organizar' => [

                'lista' => 'organizar.lista',
                'ver_listas_grupo' => 'ver_listas_grupo', // Legacy
            ],
        ],
        'configuracion_academica' => [
            'conf_periodo_lectivo' => [
                'ver' => 'conf_periodo_lectivo.index',
                'mostrar' => 'conf_periodo_lectivo.show',
                'crear' => 'conf_periodo_lectivo.create',
                'editar' => 'conf_periodo_lectivo.update',
                'eliminar' => 'conf_periodo_lectivo.delete',
                'sincronizar' => 'conf_periodo_lectivo.sync',
            ],
            'config_modalidad' => [
                'ver' => 'config_modalidad.index',
                'mostrar' => 'config_modalidad.show',
                'crear' => 'config_modalidad.create',
                'editar' => 'config_modalidad.update',
                'eliminar' => 'config_modalidad.delete',
                'sincronizar' => 'config_modalidad.sync',
            ],
            'config_grado' => [
                'ver' => 'config_grado.index',
                'mostrar' => 'config_grado.show',
                'crear' => 'config_grado.create',
                'editar' => 'config_grado.update',
                'eliminar' => 'config_grado.delete',
                'sincronizar' => 'config_grado.sync',
            ],
            'config_seccion' => [
                'ver' => 'config_seccion.index',
                'mostrar' => 'config_seccion.show',
                'crear' => 'config_seccion.create',
                'editar' => 'config_seccion.update',
                'eliminar' => 'config_seccion.delete',
                'sincronizar' => 'config_seccion.sync',
            ],
            'config_turnos' => [
                'ver' => 'config_turnos.index',
                'mostrar' => 'config_turnos.show',
                'crear' => 'config_turnos.create',
                'editar' => 'config_turnos.update',
                'eliminar' => 'config_turnos.delete',
                'sincronizar' => 'config_turnos.sync',
            ],
            'config_not_escala' => [
                'ver' => 'config_not_escala.index',

                'crear' => 'config_not_escala.create',
                'editar' => 'config_not_escala.update',
                'eliminar' => 'config_not_escala.delete',
            ],
            'config_not_semestre' => [
                'ver' => 'config_not_semestre.index',

                'crear' => 'config_not_semestre.create',

                'eliminar' => 'config_not_semestre.delete',
            ],
            'not_asignatura_grado' => [
                'ver' => 'not_asignatura_grado.index',
                'mostrar' => 'not_asignatura_grado.show',
                'crear' => 'not_asignatura_grado.create',
                'editar' => 'not_asignatura_grado.update',
                'eliminar' => 'not_asignatura_grado.delete',

            ],
            'not_materias_areas' => [
                'ver' => 'not_materias_areas.index',
                'mostrar' => 'not_materias_areas.show',
                'crear' => 'not_materias_areas.create',
                'editar' => 'not_materias_areas.update',
                'eliminar' => 'not_materias_areas.delete',

            ],
            'not_materias' => [
                'ver' => 'not_materias.index',
                'mostrar' => 'not_materias.show',
                'crear' => 'not_materias.create',
                'editar' => 'not_materias.update',
                'eliminar' => 'not_materias.delete',

            ],
            'config_grupos' => [
                'ver' => 'config_grupos.index',
                'mostrar' => 'config_grupos.show',
                'crear' => 'config_grupos.create',
                'editar' => 'config_grupos.update',
                'eliminar' => 'config_grupos.delete',
                'sincronizar' => 'config_grupos.sync',
                'filtrar' => 'config_grupos.filter',
            ],
            'config_plan_pago' => [
                'ver' => 'config_plan_pago.index',
                'mostrar' => 'config_plan_pago.show',
                'crear' => 'config_plan_pago.store',
                'editar' => 'config_plan_pago.update',
                'eliminar' => 'config_plan_pago.destroy',
                'buscar' => 'config_plan_pago.search',
                'inactivos' => 'config_plan_pago.inactive',
                'por_periodo' => 'config_plan_pago.by_periodo',
                'cambiar_estado' => 'config_plan_pago.toggle_status',
                'activar' => 'config_plan_pago.activate',
                'desactivar' => 'config_plan_pago.deactivate',
                'ver_todos' => 'config_plan_pago.getall',
            ],
            'config_plan_pago_detalle' => [
                'ver' => 'config_plan_pago_detalle.index',
                'mosrar' => 'config_plan_pago_detalle.show',
                'crear' => 'config_plan_pago_detalle.store',
                'editar' => 'config_plan_pago_detalle.update',
                'eliminar' => 'config_plan_pago_detalle.destroy',
                'buscar' => 'config_plan_pago_detalle.search',
                'por_plan' => 'config_plan_pago_detalle.by_plan',
                'colegiaturas' => 'config_plan_pago_detalle.colegiaturas',
                'por_mes' => 'config_plan_pago_detalle.by_mes',
                'por_moneda' => 'config_plan_pago_detalle.by_moneda',
                'duplicar' => 'config_plan_pago_detalle.duplicate',
                'check_codigo' => 'config_plan_pago_detalle.check_codigo',
                'check_nombre' => 'config_plan_pago_detalle.check_nombre',
                'ver_todos' => 'config_plan_pago_detalle.getall',
            ],
            'observaciones' => [
                'ver' => 'observaciones.ver',
                'editar' => 'observaciones.editar',
            ],
            'asistencias' => [
                'ver' => 'asistencias.ver',
                'registrar' => 'asistencias.registrar',
            ],
            'config_parametros' => [
                'ver' => 'config_parametros.show',
                'actualizar_crear' => 'config_parametros.update',
            ],
            'permisos' => [
                'ver' => 'configuracion_academica.permisos.ver',
                'editar' => 'configuracion_academica.permisos.editar',
            ],
            'horarios' => [
                'ver' => 'configuracion_academica.horarios.ver',
                'generar' => 'configuracion_academica.horarios.generar',
                'editar' => 'configuracion_academica.horarios.editar', // Mover bloques, asignar manual
                'configurar' => 'configuracion_academica.horarios.configurar', // Aulas, Bloques
                'eliminar' => 'configuracion_academica.horarios.eliminar',

                'configurar_asignaturas' => 'horarios.asignaturas.index', // Nueva pantalla
                'editar_asignaturas' => 'horarios.asignaturas.edit', // Editar horas/bloques
            ],

        ],
        'auditoria' => [
            'auditoria' => [
                'ver' => 'auditoria.ver',
            ],
            'login_logs' => [
                'ver' => 'login_logs.ver',
            ],
        ],
        'inventario' => [
            'productos' => [
                'ver' => 'inventario_productos.index',
                'mostrar' => 'inventario_productos.show',
                'crear' => 'inventario_productos.create',
                'editar' => 'inventario_productos.update',
                'eliminar' => 'inventario_productos.delete',
                'buscar' => 'inventario_productos.search',
                'stock' => 'inventario_productos.stock',
                'sincronizar' => 'inventario_productos.sync',
            ],
            'categorias' => [
                'ver' => 'inventario_categorias.index',

                'crear' => 'inventario_categorias.create',
                'editar' => 'inventario_categorias.update',
                'eliminar' => 'inventario_categorias.delete',

            ],
            'movimientos' => [
                'ver' => 'inventario_movimientos.index',
                'mostrar' => 'inventario_movimientos.show',
                'crear' => 'inventario_movimientos.create',
                'editar' => 'inventario_movimientos.update',
                'eliminar' => 'inventario_movimientos.delete',
                'sincronizar' => 'inventario_movimientos.sync',
            ],
            'reportes' => [
                'ver' => 'inventario.reportes.index',
            ],
            'reporte_stock' => [
                'ver' => 'inventario.reporte_stock.ver',
                'exportar' => 'inventario.reporte_stock.exportar',
            ],
        ],
        'arqueo' => [
            'config_arqueo_moneda' => [
                'index' => 'config_arqueo_moneda.index',
                'show' => 'config_arqueo_moneda.show',
                'store' => 'config_arqueo_moneda.store',
                'update' => 'config_arqueo_moneda.update',
                'destroy' => 'config_arqueo_moneda.destroy',

            ],

            'arqueo_caja' => [
                'acceso' => 'arqueo_caja'
            ],
        ],
        'mensajeria' => [
            'mensajes' => [
                'ver' => 'ver_mensajes',
                'redactar' => 'redactar_mensaje',
            ],
        ],
        'agenda' => [
            'eventos' => [
                'ver' => 'agenda.eventos.ver',
                'crear' => 'agenda.eventos.crear',
                'editar' => 'agenda.eventos.editar',
                'eliminar' => 'agenda.eventos.eliminar',
            ],
            'planes_clases' => [
                'ver' => 'agenda.planes_clases.ver',
                'crear' => 'agenda.planes_clases.crear',
                'editar' => 'agenda.planes_clases.editar',
                'eliminar' => 'agenda.planes_clases.eliminar',
                'ver_todos' => 'agenda.planes_clases.ver_todos', // Admin permission
            ],
            'avisos' => [
                'ver' => 'avisos.ver',
                'crear' => 'avisos.crear',
                'editar' => 'avisos.editar',
                'eliminar' => 'avisos.eliminar',
                'estadisticas' => 'avisos.estadisticas',
            ],
        ],
    ];

    /**
     * Obtener todos los permisos disponibles
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        return self::PERMISSIONS;
    }

    /**
     * Obtener permisos de un módulo específico
     *
     * @param string $module
     * @param string|null $category
     * @return array|null
     */
    public function getModulePermissions(string $module, ?string $category = null): ?array
    {
        if ($category) {
            return self::PERMISSIONS[$category][$module] ?? null;
        }

        // Buscar en todas las categorías
        foreach (self::PERMISSIONS as $categoryData) {
            if (isset($categoryData[$module])) {
                return $categoryData[$module];
            }
        }

        return null;
    }

    /**
     * Obtener todas las categorías disponibles
     *
     * @return array
     */
    public function getCategories(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    /**
     * Obtener todos los módulos de una categoría
     *
     * @param string $category
     * @return array
     */
    public function getCategoryModules(string $category): array
    {
        return array_keys(self::PERMISSIONS[$category] ?? []);
    }

    /**
     * Obtener todos los módulos disponibles (de todas las categorías)
     *
     * @return array
     */
    public function getAllModules(): array
    {
        $modules = [];
        foreach (self::PERMISSIONS as $categoryData) {
            $modules = array_merge($modules, array_keys($categoryData));
        }
        return array_unique($modules);
    }

    /**
     * Verificar si existe un permiso específico
     *
     * @param string $module
     * @param string $action
     * @param string|null $category
     * @return bool
     */
    public function permissionExists(string $module, string $action, ?string $category = null): bool
    {
        if ($category) {
            return isset(self::PERMISSIONS[$category][$module][$action]);
        }

        // Buscar en todas las categorías
        foreach (self::PERMISSIONS as $categoryData) {
            if (isset($categoryData[$module][$action])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener el nombre completo del permiso
     *
     * @param string $module
     * @param string $action
     * @param string|null $category
     * @return string|null
     */
    public function getPermissionName(string $module, string $action, ?string $category = null): ?string
    {
        if ($category) {
            return self::PERMISSIONS[$category][$module][$action] ?? null;
        }

        // Buscar en todas las categorías
        foreach (self::PERMISSIONS as $categoryData) {
            if (isset($categoryData[$module][$action])) {
                return $categoryData[$module][$action];
            }
        }

        return null;
    }

    /**
     * Obtener todos los permisos en formato plano
     * Útil para seeders o asignación masiva
     *
     * @return array
     */
    public function getFlatPermissions(): array
    {
        $flatPermissions = [];

        foreach (self::PERMISSIONS as $category => $modules) {
            foreach ($modules as $module => $actions) {
                foreach ($actions as $action => $permission) {
                    $flatPermissions[] = [
                        'category' => $category,
                        'module' => $module,
                        'action' => $action,
                        'permission' => $permission,
                        'display_name' => ucfirst($category) . ' - ' . ucfirst($module) . ' - ' . ucfirst($action),
                    ];
                }
            }
        }

        return $flatPermissions;
    }

    /**
     * Obtener permisos agrupados por categoría y módulo con información adicional
     *
     * @return array
     */
    public function getGroupedPermissions(): array
    {
        $groupedPermissions = [];

        foreach (self::PERMISSIONS as $category => $modules) {
            $groupedPermissions[$category] = [
                'category_name' => ucfirst($category),
                'modules' => []
            ];

            foreach ($modules as $module => $actions) {
                $groupedPermissions[$category]['modules'][$module] = [
                    'module_name' => ucfirst($module),
                    'permissions' => []
                ];

                foreach ($actions as $action => $permission) {
                    $groupedPermissions[$category]['modules'][$module]['permissions'][] = [
                        'action' => $action,
                        'permission' => $permission,
                        'display_name' => ucfirst($action),
                    ];
                }
            }
        }

        return $groupedPermissions;
    }

    /**
     * Validar una lista de permisos
     *
     * @param array $permissions
     * @return array [valid => [], invalid => []]
     */
    public function validatePermissions(array $permissions): array
    {
        $valid = [];
        $invalid = [];

        $allPermissions = $this->getFlatPermissions();
        $validPermissionNames = array_column($allPermissions, 'permission');

        foreach ($permissions as $permission) {
            if (in_array($permission, $validPermissionNames)) {
                $valid[] = $permission;
            } else {
                $invalid[] = $permission;
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid
        ];
    }

    /**
     * Obtener permisos por categoría de acción
     *
     * @param string $actionType (ver, crear, editar, eliminar, etc.)
     * @return array
     */
    public function getPermissionsByAction(string $actionType): array
    {
        $permissions = [];

        foreach (self::PERMISSIONS as $category => $modules) {
            foreach ($modules as $module => $actions) {
                if (isset($actions[$actionType])) {
                    $permissions[] = [
                        'category' => $category,
                        'module' => $module,
                        'permission' => $actions[$actionType],
                        'display_name' => ucfirst($category) . ' - ' . ucfirst($module) . ' - ' . ucfirst($actionType)
                    ];
                }
            }
        }

        return $permissions;
    }

    /**
     * Obtener todos los permisos con información detallada
     * Incluye categoría, módulo y permisos de cada elemento
     */
    public function getAllPermissionsDetailed(): array
    {
        $detailedPermissions = [];

        foreach (self::PERMISSIONS as $category => $modules) {
            foreach ($modules as $module => $actions) {
                $detailedPermissions[] = [
                    'categoria' => $category,
                    'modulo' => $module,
                    'permisos' => $actions
                ];
            }
        }

        return $detailedPermissions;
    }

    /**
     * Obtener todos los permisos en formato plano con información de categoría y módulo
     */
    public function getAllPermissionsFlatDetailed(): array
    {
        $flatPermissions = [];

        foreach (self::PERMISSIONS as $category => $modules) {
            foreach ($modules as $module => $actions) {
                foreach ($actions as $action => $permission) {
                    $flatPermissions[] = [
                        'categoria' => $category,
                        'modulo' => $module,
                        'permiso' => $permission
                    ];
                }
            }
        }

        return $flatPermissions;
    }

    /**
     * Generar array para seeder de permisos
     *
     * @return array
     */
    public function generateSeederData(): array
    {
        $seederData = [];
        $timestamp = now();

        foreach ($this->getFlatPermissions() as $permissionData) {
            $seederData[] = [
                'name' => $permissionData['permission'],
                'guard_name' => 'api',
                'category' => $permissionData['category'],
                'module' => $permissionData['module'],
                'action' => $permissionData['action'],
                'display_name' => $permissionData['display_name'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        return $seederData;
    }

    /**
     * Obtener permisos de una categoría específica
     *
     * @param string $category
     * @return array
     */
    public function getCategoryPermissions(string $category): array
    {
        return self::PERMISSIONS[$category] ?? [];
    }

    /**
     * Obtener permisos agrupados solo por módulo (sin categorías)
     * Para compatibilidad con código existente
     *
     * @return array
     */
    public function getModuleGroupedPermissions(): array
    {
        $modulePermissions = [];

        foreach (self::PERMISSIONS as $category => $modules) {
            foreach ($modules as $module => $actions) {
                if (!isset($modulePermissions[$module])) {
                    $modulePermissions[$module] = [
                        'module_name' => ucfirst($module),
                        'category' => $category,
                        'permissions' => []
                    ];
                }

                foreach ($actions as $action => $permission) {
                    $modulePermissions[$module]['permissions'][] = [
                        'action' => $action,
                        'permission' => $permission,
                        'display_name' => ucfirst($action),
                    ];
                }
            }
        }

        return $modulePermissions;
    }
}
