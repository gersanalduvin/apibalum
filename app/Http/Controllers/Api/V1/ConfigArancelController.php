<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigArancelRequest;
use App\Services\ConfigArancelService;
use App\Services\ConfigCatalogoCuentasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigArancelController extends Controller
{
    public function __construct(
        private ConfigArancelService $configArancelService,
        private ConfigCatalogoCuentasService $configCatalogoCuentasService
    ) {}

    /**
     * Obtener todos los aranceles paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            
            // Obtener filtros de la URL
            $filters = [];
            
            if ($request->has('q')) {
                $filters['q'] = $request->get('q');
            }
            
            if ($request->has('codigo')) {
                $filters['codigo'] = $request->get('codigo');
            }
            
            if ($request->has('nombre')) {
                $filters['nombre'] = $request->get('nombre');
            }
            
            if ($request->has('moneda')) {
                $filters['moneda'] = filter_var($request->get('moneda'), FILTER_VALIDATE_BOOLEAN);
            }
            
            if ($request->has('activo')) {
                $filters['activo'] = filter_var($request->get('activo'), FILTER_VALIDATE_BOOLEAN);
            }
            
            if ($request->has('precio_min')) {
                $filters['precio_min'] = $request->get('precio_min');
            }
            
            if ($request->has('precio_max')) {
                $filters['precio_max'] = $request->get('precio_max');
            }
            
            // Si hay filtros, usar búsqueda paginada, sino obtener todos paginados
            if (!empty($filters)) {
                $aranceles = $this->configArancelService->searchArancelesPaginated($filters, $perPage);
            } else {
                $aranceles = $this->configArancelService->getAllArancelesPaginated($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $aranceles,
                'message' => 'Aranceles obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener todos los aranceles sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $aranceles = $this->configArancelService->getAllAranceles();

            return response()->json([
                'success' => true,
                'data' => $aranceles,
                'message' => 'Todos los aranceles obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Crear un nuevo arancel
     */
    public function store(ConfigArancelRequest $request): JsonResponse
    {
        try {
            $arancel = $this->configArancelService->createArancel($request->validated());

            return response()->json([
                'success' => true,
                'data' => $arancel,
                'message' => 'Arancel creado exitosamente'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Mostrar un arancel específico por ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $arancel = $this->configArancelService->getArancelById($id);

            return response()->json([
                'success' => true,
                'data' => $arancel,
                'message' => 'Arancel obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }

    /**
     * Mostrar un arancel específico por UUID
     */
    public function showByUuid(string $uuid): JsonResponse
    {
        try {
            $arancel = $this->configArancelService->getArancelByUuid($uuid);

            return response()->json([
                'success' => true,
                'data' => $arancel,
                'message' => 'Arancel obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }

    /**
     * Mostrar un arancel específico por código
     */
    public function showByCodigo(string $codigo): JsonResponse
    {
        try {
            $arancel = $this->configArancelService->getArancelByCodigo($codigo);

            return response()->json([
                'success' => true,
                'data' => $arancel,
                'message' => 'Arancel obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }

    /**
     * Actualizar un arancel por ID
     */
    public function update(ConfigArancelRequest $request, int $id): JsonResponse
    {
        try {
            $arancel = $this->configArancelService->updateArancel($id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $arancel,
                'message' => 'Arancel actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Actualizar un arancel por UUID
     */
    public function updateByUuid(ConfigArancelRequest $request, string $uuid): JsonResponse
    {
        try {
            $arancel = $this->configArancelService->updateArancelByUuid($uuid, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $arancel,
                'message' => 'Arancel actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Eliminar un arancel por ID
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->configArancelService->deleteArancel($id);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Arancel eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Eliminar un arancel por UUID
     */
    public function destroyByUuid(string $uuid): JsonResponse
    {
        try {
            $this->configArancelService->deleteArancelByUuid($uuid);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Arancel eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Buscar aranceles con filtros
     */
    public function search(ConfigArancelRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $perPage = $request->get('per_page');

            if ($perPage) {
                $aranceles = $this->configArancelService->searchArancelesPaginated($filters, $perPage);
            } else {
                $aranceles = $this->configArancelService->searchAranceles($filters);
            }

            return response()->json([
                'success' => true,
                'data' => $aranceles,
                'message' => 'Búsqueda de aranceles realizada exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener aranceles activos
     */
    public function active(): JsonResponse
    {
        try {
            $aranceles = $this->configArancelService->getActiveAranceles();

            return response()->json([
                'success' => true,
                'data' => $aranceles,
                'message' => 'Aranceles activos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener aranceles por moneda
     */
    public function byMoneda(Request $request): JsonResponse
    {
        try {
            $moneda = filter_var($request->get('moneda'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
            if ($moneda === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro moneda es requerido (true para soles, false para dólares)',
                    'data' => null
                ], 400);
            }

            $aranceles = $this->configArancelService->getArancelesByMoneda($moneda);

            return response()->json([
                'success' => true,
                'data' => $aranceles,
                'message' => 'Aranceles por moneda obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de aranceles
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->configArancelService->getArancelesStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas de aranceles obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Marcar aranceles como sincronizados
     */
    public function markSynced(ConfigArancelRequest $request): JsonResponse
    {
        try {
            $uuids = $request->validated()['uuids'];
            $this->configArancelService->markArancelesAsSynced($uuids);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Aranceles marcados como sincronizados exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * Obtener aranceles actualizados después de una fecha
     */
    public function updatedAfter(Request $request): JsonResponse
    {
        try {
            $date = $request->get('updated_after');
            
            if (!$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro updated_after es requerido',
                    'data' => null
                ], 400);
            }

            $aranceles = $this->configArancelService->getArancelesUpdatedAfter($date);

            return response()->json([
                'success' => true,
                'data' => $aranceles,
                'message' => 'Aranceles actualizados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener aranceles no sincronizados
     */
    public function notSynced(): JsonResponse
    {
        try {
            $aranceles = $this->configArancelService->getNotSyncedAranceles();

            return response()->json([
                'success' => true,
                'data' => $aranceles,
                'message' => 'Aranceles no sincronizados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener catálogo de cuentas para aranceles
     */
    public function getCatalogoCuentas(): JsonResponse
    {
        try {
            $cuentas = $this->configCatalogoCuentasService->getCuentasMovimiento();

            return response()->json([
                'success' => true,
                'data' => $cuentas,
                'message' => 'Catálogo de cuentas obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}