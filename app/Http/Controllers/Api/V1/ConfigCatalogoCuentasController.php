<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigCatalogoCuentasRequest;
use App\Services\ConfigCatalogoCuentasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigCatalogoCuentasController extends Controller
{
    public function __construct(private ConfigCatalogoCuentasService $service) {}

    /**
     * Obtener todas las cuentas paginadas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = [
                'search' => $request->get('search'),
                'tipo' => $request->get('tipo'),
                'nivel' => $request->get('nivel'),
                'activo' => $request->get('activo'),
                'padre_id' => $request->get('padre_id')
            ];
            
            $cuentas = $this->service->getAllCuentasPaginated($perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => 'Cuentas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cuentas: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener todas las cuentas sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $cuentas = $this->service->getAllCuentas();

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => 'Todas las cuentas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cuentas: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Crear una nueva cuenta
     */
    public function store(ConfigCatalogoCuentasRequest $request): JsonResponse
    {
        $result = $this->service->createCuenta($request->validated());

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'message' => $result['message']
        ], $result['success'] ? 201 : 400);
    }

    /**
     * Obtener una cuenta específica
     */
    public function show(int $id): JsonResponse
    {
        try {
            $cuenta = $this->service->getCuentaById($id);

            if (!$cuenta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cuenta no encontrada',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $cuenta,
                'message' => 'Cuenta obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cuenta: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Actualizar una cuenta
     */
    public function update(ConfigCatalogoCuentasRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateCuenta($id, $request->validated());

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'message' => $result['message']
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Eliminar una cuenta
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteCuenta($id);

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'message' => $result['message']
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Filtrar cuentas por diferentes criterios
     */
    public function filtrar(Request $request): JsonResponse
    {
        try {
            $filtro = $request->get('filtro');
            $valor = $request->get('valor');
            $cuentas = collect();

            switch ($filtro) {
                case 'tipo':
                    if (!in_array($valor, ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Tipo de cuenta no válido',
                            'data' => null
                        ], 400);
                    }
                    $cuentas = $this->service->getCuentasPorTipo($valor);
                    break;

                case 'nivel':
                    if (!is_numeric($valor) || $valor < 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nivel no válido',
                            'data' => null
                        ], 400);
                    }
                    $cuentas = $this->service->getCuentasPorNivel((int)$valor);
                    break;

                case 'naturaleza':
                    if (!in_array($valor, ['deudora', 'acreedora'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Naturaleza no válida',
                            'data' => null
                        ], 400);
                    }
                    $cuentas = $this->service->getCuentasPorNaturaleza($valor);
                    break;

                case 'moneda':
                    $esUsd = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                    $cuentas = $this->service->getCuentasPorMoneda($esUsd);
                    break;

                case 'grupo':
                    $cuentas = $this->service->getCuentasGrupo();
                    break;

                case 'movimiento':
                    $cuentas = $this->service->getCuentasMovimiento();
                    break;

                case 'hijas':
                    if (!is_numeric($valor)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'ID de cuenta padre no válido',
                            'data' => null
                        ], 400);
                    }
                    $cuentas = $this->service->getCuentasHijas((int)$valor);
                    break;

                case 'raiz':
                    $cuentas = $this->service->getCuentasRaiz();
                    break;

                case 'arbol':
                    $cuentas = $this->service->getArbolCuentas();
                    break;

                case 'buscar':
                    if (empty($valor)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Término de búsqueda requerido',
                            'data' => null
                        ], 400);
                    }
                    $cuentas = $this->service->buscarCuentas($valor);
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Filtro no válido. Filtros disponibles: tipo, nivel, naturaleza, moneda, grupo, movimiento, hijas, raiz, arbol, buscar',
                        'data' => null
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => "Cuentas filtradas por {$filtro} exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar cuentas: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener el catálogo de cuentas en estructura jerárquica (árbol)
     */
    public function arbol(): JsonResponse
    {
        try {
            $cuentas = $this->service->getArbolCuentas();

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => 'Árbol de cuentas obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el árbol de cuentas: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener cuenta por código
     */
    public function porCodigo(string $codigo): JsonResponse
    {
        try {
            $cuenta = $this->service->getCuentaByCodigo($codigo);

            if (!$cuenta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cuenta no encontrada',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $cuenta,
                'message' => 'Cuenta obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cuenta: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del catálogo
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $estadisticas = $this->service->getEstadisticas();

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Sincronizar cuentas (para modo offline)
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $cuentas = $request->get('cuentas', []);
            
            if (empty($cuentas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se proporcionaron cuentas para sincronizar',
                    'data' => null
                ], 400);
            }

            $result = $this->service->syncCuentas($cuentas);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'],
                'message' => $result['message']
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la sincronización: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener cuentas no sincronizadas
     */
    public function noSincronizadas(): JsonResponse
    {
        try {
            $cuentas = $this->service->getCuentasNoSincronizadas();

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => 'Cuentas no sincronizadas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cuentas no sincronizadas: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener cuentas actualizadas después de una fecha
     */
    public function actualizadasDespues(Request $request): JsonResponse
    {
        try {
            $fecha = $request->get('fecha');
            
            if (!$fecha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fecha requerida',
                    'data' => null
                ], 400);
            }

            $cuentas = $this->service->getCuentasActualizadasDespues($fecha);

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => 'Cuentas actualizadas obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cuentas actualizadas: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Marcar cuenta como sincronizada
     */
    public function marcarSincronizada(Request $request): JsonResponse
    {
        try {
            $uuid = $request->get('uuid');
            
            if (!$uuid) {
                return response()->json([
                    'success' => false,
                    'message' => 'UUID requerido',
                    'data' => null
                ], 400);
            }

            $result = $this->service->marcarComoSincronizada($uuid);

            return response()->json([
                'success' => $result,
                'data' => null,
                'message' => $result ? 'Cuenta marcada como sincronizada' : 'Error al marcar cuenta como sincronizada'
            ], $result ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como sincronizada: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}