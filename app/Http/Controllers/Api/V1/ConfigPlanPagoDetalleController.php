<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigPlanPagoDetalleRequest;
use App\Services\ConfigPlanPagoDetalleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigPlanPagoDetalleController extends Controller
{
    public function __construct(private ConfigPlanPagoDetalleService $configPlanPagoDetalleService) {}

    /**
     * Obtener todos los detalles de planes de pago paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search', '');
            $planPagoId = $request->get('plan_pago_id');
            $esColegiatura = $request->get('es_colegiatura');
            $mes = $request->get('mes');
            $moneda = $request->get('moneda');

            // Si hay filtros específicos, usar búsqueda con filtros
            if ($search || $planPagoId || $esColegiatura !== null || $mes || $moneda !== null) {
                $detalles = $this->configPlanPagoDetalleService->searchWithFiltersPaginated(
                    $search,
                    $planPagoId,
                    $esColegiatura,
                    $mes,
                    $moneda,
                    $perPage
                );
            } else {
                $detalles = $this->configPlanPagoDetalleService->getAllPaginated($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $detalles,
                'message' => 'Detalles de planes de pago obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles de planes de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los detalles de planes de pago
     */
    public function getall(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $detalles = $this->configPlanPagoDetalleService->getAllPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $detalles,
                'message' => 'Todos los detalles de planes de pago obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener todos los detalles de planes de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar detalles de planes de pago
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $perPage = $request->get('per_page', 10);
            
            $detalles = $this->configPlanPagoDetalleService->searchPaginated($search, $perPage);

            return response()->json([
                'success' => true,
                'data' => $detalles,
                'message' => 'Búsqueda de detalles de planes de pago realizada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar detalles de planes de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles por plan de pago
     */
    public function getByPlanPago(Request $request, $planPagoId)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $resultado = $this->configPlanPagoDetalleService->getPlanPagoWithDetallesPaginated($planPagoId, $perPage);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Plan de pago y detalles obtenidos exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener detalles de colegiaturas
     */
    public function getColegiaturas(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $detalles = $this->configPlanPagoDetalleService->getColegiaturasPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $detalles,
                'message' => 'Colegiaturas obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las colegiaturas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles por mes
     */
    public function getByMes(Request $request, string $mes): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $detalles = $this->configPlanPagoDetalleService->getByMesPaginated($mes, $perPage);

            return response()->json([
                'success' => true,
                'data' => $detalles,
                'message' => "Detalles del mes {$mes} obtenidos exitosamente"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles del mes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles por moneda
     */
    public function getByMoneda(Request $request, bool $moneda): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $detalles = $this->configPlanPagoDetalleService->getByMonedaPaginated($moneda, $perPage);

            $monedaTexto = $moneda ? 'Dólar' : 'Córdoba';

            return response()->json([
                'success' => true,
                'data' => $detalles,
                'message' => "Detalles en {$monedaTexto} obtenidos exitosamente"
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles por moneda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo detalle de plan de pago
     */
    public function store(ConfigPlanPagoDetalleRequest $request): JsonResponse
    {
        try {
            $detalle = $this->configPlanPagoDetalleService->createConfigPlanPagoDetalle($request->validated());

            return response()->json([
                'success' => true,
                'data' => $detalle,
                'message' => 'Detalle de plan de pago creado exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el detalle de plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un detalle de plan de pago específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $detalle = $this->configPlanPagoDetalleService->getById($id);

            if (!$detalle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de plan de pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $detalle,
                'message' => 'Detalle de plan de pago obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el detalle de plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un detalle de plan de pago
     */
    public function update(ConfigPlanPagoDetalleRequest $request, int $id): JsonResponse
    {
        try {
            $detalle = $this->configPlanPagoDetalleService->updateConfigPlanPagoDetalle($id, $request->validated());

            if (!$detalle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de plan de pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $detalle,
                'message' => 'Detalle de plan de pago actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el detalle de plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un detalle de plan de pago
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->configPlanPagoDetalleService->deleteConfigPlanPagoDetalle($id);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de plan de pago no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detalle de plan de pago eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el detalle de plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar un detalle de plan de pago
     */
    public function duplicate(int $id): JsonResponse
    {
        try {
            $detalle = $this->configPlanPagoDetalleService->duplicate($id);

            if (!$detalle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de plan de pago no encontrado para duplicar'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $detalle,
                'message' => 'Detalle de plan de pago duplicado exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al duplicar el detalle de plan de pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si existe un código en un plan de pago
     */
    public function checkCodigo(Request $request): JsonResponse
    {
        try {
            $codigo = $request->get('codigo');
            $planPagoId = $request->get('plan_pago_id');
            $excludeId = $request->get('exclude_id');

            if (!$codigo || !$planPagoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código y plan de pago son requeridos'
                ], 400);
            }

            $exists = $this->configPlanPagoDetalleService->existsByCodigo($codigo, $planPagoId, $excludeId);

            return response()->json([
                'success' => true,
                'data' => ['exists' => $exists],
                'message' => $exists ? 'El código ya existe en este plan de pago' : 'El código está disponible'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el código: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si existe un nombre en un plan de pago
     */
    public function checkNombre(Request $request): JsonResponse
    {
        try {
            $nombre = $request->get('nombre');
            $planPagoId = $request->get('plan_pago_id');
            $excludeId = $request->get('exclude_id');

            if (!$nombre || !$planPagoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre y plan de pago son requeridos'
                ], 400);
            }

            $exists = $this->configPlanPagoDetalleService->existsByNombre($nombre, $planPagoId, $excludeId);

            return response()->json([
                'success' => true,
                'data' => ['exists' => $exists],
                'message' => $exists ? 'El nombre ya existe en este plan de pago' : 'El nombre está disponible'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el nombre: ' . $e->getMessage()
            ], 500);
        }
    }
}