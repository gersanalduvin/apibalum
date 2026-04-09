<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigFormaPagoRequest;
use App\Services\ConfigFormaPagoService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigFormaPagoController extends Controller
{
    public function __construct(private ConfigFormaPagoService $configFormaPagoService) {}

    /**
     * Obtener todas las formas de pago paginadas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $activo = $request->get('activo');
            $esEfectivo = $request->get('es_efectivo');
            $moneda = $request->get('moneda');

            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($activo !== null) $filters['activo'] = filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($esEfectivo !== null) $filters['es_efectivo'] = filter_var($esEfectivo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($moneda !== null) $filters['moneda'] = filter_var($moneda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!empty($filters)) {
                $formasPago = $this->configFormaPagoService->searchWithFiltersPaginated($filters, $perPage);
            } else {
                $formasPago = $this->configFormaPagoService->getAllConfigFormasPagoPaginated($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $formasPago,
                'message' => 'Formas de pago obtenidas exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las formas de pago: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener todas las formas de pago sin paginación
     */
    public function getall(Request $request): JsonResponse
    {
        try {
            $active = $request->get('active');
            $esEfectivo = $request->get('es_efectivo');
            $moneda = $request->get('moneda');

            if ($active !== null && filter_var($active, FILTER_VALIDATE_BOOLEAN)) {
                $formasPago = $this->configFormaPagoService->getAllActiveConfigFormasPago();
            } elseif ($esEfectivo !== null) {
                $formasPago = $this->configFormaPagoService->getAllByEfectivo(filter_var($esEfectivo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
            } elseif ($moneda !== null) {
                $formasPago = $this->configFormaPagoService->searchWithFiltersPaginated(['moneda' => filter_var($moneda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)], 1000);
            } else {
                $formasPago = $this->configFormaPagoService->getAllConfigFormasPago();
            }

            return response()->json([
                'success' => true,
                'data' => $formasPago,
                'message' => 'Formas de pago obtenidas exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las formas de pago: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Crear una nueva forma de pago
     */
    public function store(ConfigFormaPagoRequest $request): JsonResponse
    {
        try {
            $formaPago = $this->configFormaPagoService->createConfigFormaPago($request->validated());

            return response()->json([
                'success' => true,
                'data' => $formaPago,
                'message' => 'Forma de pago creada exitosamente'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la forma de pago: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Obtener una forma de pago específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Verificar si es UUID o ID numérico
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
                $formaPago = $this->configFormaPagoService->getConfigFormaPagoByUuid($id);
            } else {
                $formaPago = $this->configFormaPagoService->getConfigFormaPagoById((int)$id);
            }

            return response()->json([
                'success' => true,
                'data' => $formaPago,
                'message' => 'Forma de pago obtenida exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la forma de pago: ' . $e->getMessage(),
                'data' => null
            ], 404);
        }
    }

    /**
     * Actualizar una forma de pago
     */
    public function update(ConfigFormaPagoRequest $request, string $id): JsonResponse
    {
        try {
            $formaPago = $this->configFormaPagoService->updateConfigFormaPago((int)$id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $formaPago,
                'message' => 'Forma de pago actualizada exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la forma de pago: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Eliminar una forma de pago
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->configFormaPagoService->deleteConfigFormaPago((int)$id);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Forma de pago eliminada exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la forma de pago: ' . $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Buscar formas de pago
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $term = $request->get('term', '');
            $perPage = $request->get('per_page', 15);

            if (empty($term)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El término de búsqueda es requerido',
                    'data' => null
                ], 400);
            }

            $formasPago = $this->configFormaPagoService->searchConfigFormasPagoPaginated($term, $perPage);

            return response()->json([
                'success' => true,
                'data' => $formasPago,
                'message' => 'Búsqueda realizada exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener registros no sincronizados (para modo offline)
     */
    public function unsynced(): JsonResponse
    {
        try {
            $unsyncedRecords = $this->configFormaPagoService->getUnsyncedRecords();

            return response()->json([
                'success' => true,
                'data' => $unsyncedRecords,
                'message' => 'Registros no sincronizados obtenidos exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros no sincronizados: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Marcar registro como sincronizado
     */
    public function markSynced(string $id): JsonResponse
    {
        try {
            $result = $this->configFormaPagoService->markAsSynced((int)$id);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'Registro marcado como sincronizado'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo marcar el registro como sincronizado',
                'data' => null
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como sincronizado: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener registros actualizados después de una fecha específica
     */
    public function updatedAfter(Request $request): JsonResponse
    {
        try {
            $datetime = $request->get('datetime');

            if (!$datetime) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro datetime es requerido',
                    'data' => null
                ], 400);
            }

            $records = $this->configFormaPagoService->getUpdatedAfter($datetime);

            return response()->json([
                'success' => true,
                'data' => $records,
                'message' => 'Registros actualizados obtenidos exitosamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros actualizados: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
