<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UsersArancelesRequest;
use App\Services\UsersArancelesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class UsersArancelesController extends Controller
{
    public function __construct(private UsersArancelesService $usersArancelesService) {}

    /**
     * Obtener todos los aranceles paginados
     */
    public function index(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'user_id',
                'estado',
                'rubro_id',
                'con_recargo',
                'con_saldo_pendiente'
            ]);

            $perPage = $request->input('per_page', 15);

            $aranceles = $this->usersArancelesService->getAllArancelesPaginated($filters, $perPage);

            return $this->successResponse($aranceles, 'Aranceles obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener los aranceles: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener todos los aranceles sin paginación
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $aranceles = $this->usersArancelesService->getAllAranceles();

            return $this->successResponse($aranceles, 'Aranceles obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener los aranceles: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * 1. Agregar Registro
     */
    public function store(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $arancel = $this->usersArancelesService->createArancel($data);

            return $this->successResponse($arancel, 'Arancel creado exitosamente', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear el arancel: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Obtener arancel por ID
     */
    public function show(string $id): JsonResponse
    {
        try {
            $arancel = $this->usersArancelesService->getArancelById($id);

            return $this->successResponse($arancel, 'Arancel obtenido exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 500;
            return $this->errorResponse('Error al obtener el arancel: ' . $e->getMessage(), [], $statusCode);
        }
    }

    /**
     * 2. Eliminar Registro
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->usersArancelesService->deleteArancel($id);

            return $this->successResponse(null, 'Arancel eliminado exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse('Error al eliminar el arancel: ' . $e->getMessage(), [], $statusCode);
        }
    }

    /**
     * 3. Anular recargos
     * Actualiza los campos: recargo_pagado=recargo, saldo_actual recalcula. campos a actualizar: fecha_recargo_anulado, recargo_anulado_por, observacion_recargo.
     */
    public function anularRecargo(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $ids = $data['ids'];

            // Preparar datos adicionales
            $updateData = [
                'fecha_recargo_anulado' => $data['fecha_recargo_anulado'] ?? now()->format('Y-m-d'),
                'recargo_anulado_por' => $data['recargo_anulado_por'] ?? auth()->id(),
                'observacion_recargo' => $data['observacion_recargo']
            ];

            $updated = $this->usersArancelesService->anularRecargo($ids, $updateData);

            return $this->successResponse([
                'registros_actualizados' => $updated,
                'ids_procesados' => $ids
            ], 'Recargos anulados exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al anular recargo: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * 7. Aplicar Beca
     */
    public function aplicarBeca(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $ids = $data['ids'];
            $beca = (float) $data['beca'];

            $updated = $this->usersArancelesService->aplicarBeca($ids, $beca);

            return $this->successResponse([
                'registros_actualizados' => $updated,
                'ids_procesados' => $ids
            ], 'Becas aplicadas exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al aplicar becas: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * 8. Aplicar Descuento
     */
    public function aplicarDescuento(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $ids = $data['ids'];
            $descuento = (float) $data['descuento'];

            $updated = $this->usersArancelesService->aplicarDescuento($ids, $descuento);

            return $this->successResponse([
                'registros_actualizados' => $updated,
                'ids_procesados' => $ids
            ], 'Descuentos aplicados exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al aplicar descuentos: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * 4. Exonerar
     * Actualiza los campos: estado=exonerado, fecha_exonerado, observacion_exonerado
     */
    public function exonerar(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $ids = $data['ids'];

            // Preparar datos adicionales
            $updateData = [
                'fecha_exonerado' => $data['fecha_exonerado'] ?? now()->format('Y-m-d'),
                'observacion_exonerado' => $data['observacion_exonerado']
            ];

            $updated = $this->usersArancelesService->exonerar($ids, $updateData);

            return $this->successResponse([
                'registros_actualizados' => $updated,
                'ids_procesados' => $ids
            ], 'Aranceles exonerados exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al exonerar aranceles: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * 5. Aplicar plan de pago
     * Busca el detalle del plan de pago seleccionado y lo aplica a la tabla users_aranceles
     */
    public function aplicarPlanPago(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $planPagoId = $data['plan_pago_id'];
            $userId = $data['user_id'];

            $resultado = $this->usersArancelesService->aplicarPlanPago($planPagoId, $userId);

            return $this->successResponse($resultado, 'Plan de pago aplicado exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al aplicar plan de pago: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * 6. Aplicar pago
     * Recibe array de IDs y actualiza: saldo_pagado=importe_total, recargo_pagado=recargo, saldo_actual=0, estado=pagado
     */
    public function aplicarPago(UsersArancelesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $ids = $data['ids'];

            $updated = $this->usersArancelesService->aplicarPago($ids);

            return $this->successResponse([
                'registros_actualizados' => $updated,
                'ids_procesados' => $ids
            ], 'Pagos aplicados exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al aplicar pagos: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Revertir pago de arancel
     */
    public function revertir(string $id): JsonResponse
    {
        try {
            $this->usersArancelesService->revertirArancel((int)$id);

            return $this->successResponse(null, 'Pago de arancel revertido exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse('Error al revertir el arancel: ' . $e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Obtener aranceles por usuario
     */
    public function getByUser(string $userId): JsonResponse
    {
        try {
            $aranceles = $this->usersArancelesService->getArancelesByUser($userId);

            return $this->successResponse($aranceles, 'Aranceles del usuario obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener aranceles del usuario: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener aranceles pendientes por usuario
     */
    public function getPendientesByUser(string $userId): JsonResponse
    {
        try {
            $aranceles = $this->usersArancelesService->getArancelesPendientesByUser($userId);

            return $this->successResponse($aranceles, 'Aranceles pendientes del usuario obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener aranceles pendientes del usuario: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener aranceles con recargo
     */
    public function getConRecargo(): JsonResponse
    {
        try {
            $aranceles = $this->usersArancelesService->getArancelesConRecargo();

            return $this->successResponse($aranceles, 'Aranceles con recargo obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener aranceles con recargo: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener aranceles con saldo pendiente
     */
    public function getConSaldoPendiente(): JsonResponse
    {
        try {
            $aranceles = $this->usersArancelesService->getArancelesConSaldoPendiente();

            return $this->successResponse($aranceles, 'Aranceles con saldo pendiente obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener aranceles con saldo pendiente: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener estadísticas de aranceles
     */
    public function getEstadisticas(): JsonResponse
    {
        try {
            $estadisticas = $this->usersArancelesService->getEstadisticas();

            return $this->successResponse($estadisticas, 'Estadísticas obtenidas exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener estadísticas: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener todos los períodos lectivos
     */
    public function getPeriodosLectivos(): JsonResponse
    {
        try {
            $periodosLectivos = $this->usersArancelesService->getPeriodosLectivos();

            return $this->successResponse($periodosLectivos, 'Períodos lectivos obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener períodos lectivos: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener planes de pago por período lectivo
     */
    public function getPlanesPagoPorPeriodo(string $periodoLectivoId): JsonResponse
    {
        try {
            $planesPago = $this->usersArancelesService->getPlanesPagoPorPeriodo($periodoLectivoId);

            return $this->successResponse($planesPago, 'Planes de pago obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener planes de pago: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Actualizar arancel (método auxiliar)
     */
    public function update(UsersArancelesRequest $request, string $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $arancel = $this->usersArancelesService->updateArancel($id, $data);

            return $this->successResponse($arancel, 'Arancel actualizado exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse('Error al actualizar el arancel: ' . $e->getMessage(), [], $statusCode);
        }
    }

    /**
     * Generar reporte PDF de aranceles del usuario
     */
    public function generarPdfReporte(Request $request, string $userId)
    {
        try {
            $filters = $request->only(['estado', 'fecha_inicio', 'fecha_fin']);
            return $this->usersArancelesService->generarPdfReporte($userId, $filters);
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar el reporte PDF: ' . $e->getMessage(), [], 500);
        }
    }
}
