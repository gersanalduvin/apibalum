<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigPlanPagoRequest;
use App\Services\ConfigPlanPagoService;
use App\Services\ConfPeriodoLectivoService;
use App\Services\ConfigCatalogoCuentasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigPlanPagoController extends Controller
{
    public function __construct(
        private ConfigPlanPagoService $configPlanPagoService,
        private ConfPeriodoLectivoService $confPeriodoLectivoService,
        private ConfigCatalogoCuentasService $configCatalogoCuentasService
    ) {}

    /**
     * Obtener todos los planes de pago paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search', '');
            $activo = $request->get('activo');
            $periodoLectivoId = $request->get('periodo_lectivo_id');

            // Si hay filtros específicos, usar búsqueda con filtros
            if ($search || $activo !== null || $periodoLectivoId) {
                $planesPago = $this->configPlanPagoService->searchWithFiltersPaginated(
                    $search,
                    $activo,
                    $periodoLectivoId,
                    $perPage
                );
            } else {
                $planesPago = $this->configPlanPagoService->getAllPaginated($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $planesPago,
                'message' => 'Planes de pago obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los planes de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los planes de pago activos paginados
     */
    public function getall(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $planesPago = $this->configPlanPagoService->getAllActivePaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $planesPago,
                'message' => 'Planes de pago activos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los planes de pago activos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los planes de pago inactivos paginados
     */
    public function getAllInactive(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $planesPago = $this->configPlanPagoService->getAllInactivePaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $planesPago,
                'message' => 'Planes de pago inactivos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los planes de pago inactivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar planes de pago
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $perPage = $request->get('per_page', 10);
            
            $planesPago = $this->configPlanPagoService->searchPaginated($search, $perPage);

            return response()->json([
                'success' => true,
                'data' => $planesPago,
                'message' => 'Búsqueda de planes de pago realizada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar planes de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener planes de pago por período lectivo
     */
    public function getByPeriodoLectivo(Request $request, int $periodoLectivoId): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $planesPago = $this->configPlanPagoService->getByPeriodoLectivoPaginated($periodoLectivoId, $perPage);

            return response()->json([
                'success' => true,
                'data' => $planesPago,
                'message' => 'Planes de pago del período lectivo obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener planes de pago del período lectivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los períodos lectivos para select
     */
    public function getPeriodosLectivos(): JsonResponse
    {
        try {
            $periodosLectivos = $this->confPeriodoLectivoService->getAllConfPeriodoLectivos();
            
            return response()->json([
                'success' => true,
                'data' => $periodosLectivos->map(function ($periodo) {
                    return [
                        'id' => $periodo->id,
                        'uuid' => $periodo->uuid,
                        'nombre' => $periodo->nombre,
                        'periodo_nota' => $periodo->periodo_nota,
                        'periodo_matricula' => $periodo->periodo_matricula
                    ];
                }),
                'message' => 'Períodos lectivos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los períodos lectivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo plan de pago
     */
    public function store(ConfigPlanPagoRequest $request): JsonResponse
    {
        try {
            $planPago = $this->configPlanPagoService->createConfigPlanPago($request->validated());

            return response()->json([
                'success' => true,
                'data' => $planPago,
                'message' => 'Plan de pago creado exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un plan de pago específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $planPago = $this->configPlanPagoService->getById($id);

            if (!$planPago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan de pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $planPago,
                'message' => 'Plan de pago obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un plan de pago
     */
    public function update(ConfigPlanPagoRequest $request, int $id): JsonResponse
    {
        try {
            $planPago = $this->configPlanPagoService->updateConfigPlanPago($id, $request->validated());

            if (!$planPago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan de pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $planPago,
                'message' => 'Plan de pago actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un plan de pago
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->configPlanPagoService->deleteConfigPlanPago($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan de pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Plan de pago eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de un plan de pago
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $planPago = $this->configPlanPagoService->toggleStatus($id);

            if (!$planPago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan de pago no encontrado'
                ], 404);
            }

            $estado = $planPago->estado ? 'activado' : 'desactivado';

            return response()->json([
                'success' => true,
                'data' => $planPago,
                'message' => "Plan de pago {$estado} exitosamente"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar un plan de pago
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $planPago = $this->configPlanPagoService->activate($id);

            if (!$planPago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan de pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $planPago,
                'message' => 'Plan de pago activado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al activar el plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desactivar un plan de pago
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $planPago = $this->configPlanPagoService->deactivate($id);

            if (!$planPago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan de pago no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $planPago,
            'message' => 'Plan de pago desactivado exitosamente'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al desactivar el plan de pago: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Obtener catálogo de cuentas para selects
 */
public function getCatalogoCuentas(): JsonResponse
{
    try {
        $cuentas = $this->configCatalogoCuentasService->getAllCuentas();
        
        // Formatear datos para selects
        $cuentasFormateadas = $cuentas->map(function ($cuenta) {
            return [
                'id' => $cuenta->id,
                'uuid' => $cuenta->uuid,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'codigo_completo' => $cuenta->codigo_completo ?? $cuenta->codigo,
                'tipo' => $cuenta->tipo,
                'nivel' => $cuenta->nivel,
                'es_grupo' => $cuenta->es_grupo,
                'permite_movimiento' => $cuenta->permite_movimiento,
                'naturaleza' => $cuenta->naturaleza,
                'moneda_usd' => $cuenta->moneda_usd,
                'padre_id' => $cuenta->padre_id,
                'estado' => $cuenta->estado
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $cuentasFormateadas,
            'message' => 'Catálogo de cuentas obtenido exitosamente'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener el catálogo de cuentas: ' . $e->getMessage()
        ], 500);
    }
}
}