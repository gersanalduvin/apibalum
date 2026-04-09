<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReciboRequest;
use App\Services\ReciboService;
use App\Services\ProductoService;
use App\Services\ConfigArancelService;
use App\Services\ConfigFormaPagoService;
use App\Services\UserService;
use App\Services\UsersArancelesService;
use App\Services\ConfPeriodoLectivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ReciboController extends Controller
{
    public function __construct(
        private ReciboService $reciboService,
        private UserService $userService,
        private ProductoService $productoService,
        private ConfigArancelService $configArancelService,
        private ConfigFormaPagoService $configFormaPagoService,
        private UsersArancelesService $usersArancelesService,
        private ConfPeriodoLectivoService $confPeriodoLectivoService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'user_id', 'estado', 'estado_not', 'tipo', 'fecha_inicio', 'fecha_fin']);
            $perPage = (int) $request->input('per_page', 15);
            $recibos = $this->reciboService->getAllRecibosPaginated($filters, $perPage);
            return $this->successResponse($recibos, 'Recibos obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener los recibos: ' . $e->getMessage(), [], 500);
        }
    }

    public function store(ReciboRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $recibo = $this->reciboService->createRecibo($data);
            return $this->successResponse($recibo, 'Recibo creado exitosamente', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear el recibo: ' . $e->getMessage(), [], 400);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user->superadmin) {
                abort_unless($user->can('recibos.eliminar_anulado'), 403, 'No tiene permiso para eliminar recibos anulados.');
            }

            $this->reciboService->deleteRecibo((int)$id);
            return $this->successResponse(null, 'Recibo eliminado exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse('Error al eliminar el recibo: ' . $e->getMessage(), [], $statusCode);
        }
    }

    public function anular(string $id): JsonResponse
    {
        try {
            $recibo = $this->reciboService->anularRecibo((int)$id);
            return $this->successResponse($recibo, 'Recibo anulado exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 400;
            return $this->errorResponse('Error al anular el recibo: ' . $e->getMessage(), [], $statusCode);
        }
    }

    public function imprimirPdf(string $id)
    {
        try {
            return $this->reciboService->generarPdf((int)$id);
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar el PDF: ' . $e->getMessage(), [], 500);
        }
    }

    public function reporte(string $id): JsonResponse
    {
        try {
            $reporte = $this->reciboService->getReporteMontos((int)$id);
            return $this->successResponse($reporte, 'Reporte de montos generado exitosamente');
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'no encontrado') ? 404 : 500;
            return $this->errorResponse('Error al generar el reporte: ' . $e->getMessage(), [], $statusCode);
        }
    }

    public function buscarAlumnos(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'nullable|string',
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $q = $request->get('q', '');
            $limit = (int) $request->get('limit', 20);

            $alumnos = $this->userService->searchUsersByType('alumno', $q, $limit);
            return $this->successResponse($alumnos, 'Alumnos buscados exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al buscar alumnos: ' . $e->getMessage(), [], 400);
        }
    }

    public function catalogoProductos(Request $request): JsonResponse
    {
        try {
            $q = $request->get('q');
            if ($q) {
                $productos = $this->productoService->buscarPorNombreConStock($q);
            } else {
                $productos = $this->productoService->getProductosActivosConStock();
            }
            return $this->successResponse($productos, 'Productos obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener productos: ' . $e->getMessage(), [], 500);
        }
    }

    public function catalogoAranceles(Request $request): JsonResponse
    {
        try {
            $q = $request->get('q');
            if ($q) {
                $aranceles = $this->configArancelService->searchAranceles(['search' => $q]);
            } else {
                $aranceles = $this->configArancelService->getActiveAranceles();
            }
            return $this->successResponse($aranceles, 'Aranceles obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener aranceles: ' . $e->getMessage(), [], 500);
        }
    }

    public function catalogoFormasPago(Request $request): JsonResponse
    {
        try {
            $q = $request->get('q');
            if ($q) {
                $formas = $this->configFormaPagoService->searchConfigFormasPago($q);
            } else {
                $formas = $this->configFormaPagoService->getAllActiveConfigFormasPago();
            }
            return $this->successResponse($formas, 'Formas de pago obtenidas exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener formas de pago: ' . $e->getMessage(), [], 500);
        }
    }

    public function periodoPlanesPago(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'periodo_lectivo_id' => 'nullable|integer|min:1'
            ]);

            $periodoId = (int) $request->get('periodo_lectivo_id');
            if ($periodoId) {
                $periodo = $this->confPeriodoLectivoService->getConfPeriodoLectivoById($periodoId);
                if (!$periodo) {
                    return $this->errorResponse('Período lectivo no encontrado', [], 404);
                }
                $planes = $this->usersArancelesService->getPlanesPagoPorPeriodo($periodoId);
                return $this->successResponse([
                    'periodo' => [
                        'id' => $periodo->id,
                        'uuid' => $periodo->uuid,
                        'nombre' => $periodo->nombre,
                    ],
                    'planes_pago_activos' => $planes,
                ], 'Período lectivo y planes de pago activos obtenidos exitosamente');
            }

            $periodos = $this->usersArancelesService->getPeriodosLectivos();
            $data = $periodos->map(function ($p) {
                $planes = $this->usersArancelesService->getPlanesPagoPorPeriodo($p['id']);
                return [
                    'periodo' => $p,
                    'planes_pago_activos' => $planes,
                ];
            });

            return $this->successResponse($data, 'Períodos lectivos con planes de pago activos obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener períodos y planes de pago: ' . $e->getMessage(), [], 500);
        }
    }

    public function crearAlumnoConPlan(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'primer_nombre' => 'required|string|max:100',
                'segundo_nombre' => 'nullable|string|max:100',
                'primer_apellido' => 'required|string|max:100',
                'segundo_apellido' => 'nullable|string|max:100',
                'sexo' => 'required|string|in:M,F',
                'email' => 'nullable|email|max:255|unique:users,email',
                'fecha_nacimiento' => 'nullable|date|before:today',
                'plan_pago_id' => 'required|integer|exists:config_plan_pago,id',
            ]);

            $data = $request->only([
                'primer_nombre',
                'segundo_nombre',
                'primer_apellido',
                'segundo_apellido',
                'fecha_nacimiento',
                'sexo',
            ]);

            // Generar email automáticamente si no viene
            if (empty($request->email)) {
                $pNombre = strtolower(trim($data['primer_nombre']));
                $pApellido = strtolower(trim($data['primer_apellido']));
                // Remover espacios y caracteres especiales básicos
                $pNombre = preg_replace('/[^a-z0-9]/', '', $pNombre);
                $pApellido = preg_replace('/[^a-z0-9]/', '', $pApellido);

                $baseUser = $pNombre . '.' . $pApellido;
                $email = $baseUser . '@cempp.com';

                $counter = 1;
                while (\App\Models\User::where('email', $email)->exists()) {
                    $email = $baseUser . $counter . '@cempp.com';
                    $counter++;
                }
                $data['email'] = $email;
            } else {
                $data['email'] = $request->email;
            }

            // Default fecha nacimiento si no se envía
            if (empty($data['fecha_nacimiento'])) {
                $data['fecha_nacimiento'] = '2010-01-01'; // Default razonable para alumno
            }

            $data['tipo_usuario'] = 'alumno';
            $data['segundo_nombre'] = $data['segundo_nombre'] ?? null;
            $data['segundo_apellido'] = $data['segundo_apellido'] ?? null;

            $alumno = $this->userService->createUser($data);

            $planId = $request->input('plan_pago_id');
            $aplicacion = null;
            if ($planId) {
                $aplicacion = $this->usersArancelesService->aplicarPlanPago((int)$planId, (int)$alumno->id);
            }

            return $this->successResponse([
                'alumno' => $alumno,
                'plan_pago_aplicacion' => $aplicacion,
            ], 'Alumno creado exitosamente' . ($planId ? ' y plan de pago aplicado' : ''));
        } catch (Exception $e) {
            return $this->errorResponse('Error al crear alumno y aplicar plan: ' . $e->getMessage(), [], 400);
        }
    }

    public function parametrosCaja(): JsonResponse
    {
        try {
            $p = \App\Models\ConfigParametros::query()->orderBy('id', 'asc')->first();
            $data = [
                'consecutivo_recibo_oficial' => (int) ($p->consecutivo_recibo_oficial ?? 0),
                'consecutivo_recibo_interno' => (int) ($p->consecutivo_recibo_interno ?? 0),
                'tasa_cambio_dolar' => (float) ($p->tasa_cambio_dolar ?? 0),
            ];
            return $this->successResponse($data, 'Parámetros de caja obtenidos exitosamente');
        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener parámetros de caja: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generar reporte PDF del historial de recibos del usuario
     */
    public function imprimirHistorialPdf(Request $request, string $userId)
    {
        try {
            $filters = $request->only(['fecha_inicio', 'fecha_fin']);
            return $this->reciboService->generarPdfHistorial((int)$userId, $filters);
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar el historial PDF: ' . $e->getMessage(), [], 500);
        }
    }
}
