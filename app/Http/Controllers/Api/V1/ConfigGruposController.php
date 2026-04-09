<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConfigGruposRequest;
use App\Services\ConfigGruposService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ConfigGruposController extends Controller
{
    public function __construct(private ConfigGruposService $configGruposService) {}

    /**
     * Obtener todos los grupos paginados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $grupos = $this->configGruposService->getAllConfigGruposPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los grupos: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener todos los grupos sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $grupos = $this->configGruposService->getAllConfigGrupos();

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los grupos: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Crear un nuevo grupo
     */
    public function store(ConfigGruposRequest $request): JsonResponse
    {
        try {
            $configGrupos = $this->configGruposService->createConfigGrupos($request->validated());

            return response()->json([
                'success' => true,
                'data' => $configGrupos,
                'message' => 'Grupo creado exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el grupo: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener un grupo específico
     */
    public function show(string $id): JsonResponse
    {
        try {
            $grupo = $this->configGruposService->getConfigGruposById((int) $id);

            return response()->json([
                'success' => true,
                'data' => $grupo,
                'message' => 'Grupo obtenido exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el grupo: ' . $e->getMessage(),
                'errors' => []
            ], 404);
        }
    }

    /**
     * Actualizar un grupo
     */
    public function update(ConfigGruposRequest $request, string $id): JsonResponse
    {
        try {
            $grupo = $this->configGruposService->updateConfigGrupos((int) $id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $grupo,
                'message' => 'Grupo actualizado exitosamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el grupo: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Eliminar un grupo
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->configGruposService->deleteConfigGrupos((int) $id);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Grupo eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el grupo: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener grupos por grado
     */
    public function byGrado(string $gradoId): JsonResponse
    {
        try {
            $grupos = $this->configGruposService->getGruposByGrado((int) $gradoId);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos por grado obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos por grado: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener grupos por sección
     */
    public function bySeccion(string $seccionId): JsonResponse
    {
        try {
            $grupos = $this->configGruposService->getGruposBySeccion((int) $seccionId);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos por sección obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos por sección: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener grupos por turno
     */
    public function byTurno(string $turnoId): JsonResponse
    {
        try {
            $grupos = $this->configGruposService->getGruposByTurno((int) $turnoId);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos por turno obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos por turno: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    // Endpoint por modalidad removido

    /**
     * Obtener grupos por docente guía
     */
    public function byDocenteGuia(string $docenteId): JsonResponse
    {
        try {
            $grupos = $this->configGruposService->getGruposByDocenteGuia((int) $docenteId);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos por docente guía obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos por docente guía: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener grupos por período lectivo
     */
    public function byPeriodoLectivo(string $periodoLectivoId): JsonResponse
    {
        try {
            $grupos = $this->configGruposService->getGruposByPeriodoLectivo((int) $periodoLectivoId);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos por período lectivo obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos por período lectivo: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener mis grupos activos (donde soy docente guía y el periodo nota es activo)
     */
    public function myActiveGroups(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Usuario no autenticado'], 401);
            }

            $grupos = $this->configGruposService->getGruposDocenteGuiaActivo($user->id);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Mis grupos activos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mis grupos activos: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener grupos filtrados por múltiples criterios
     * Acepta query params: periodo_id, grado_id, turno_id, seccion_id, docente_guia
     */
    public function byAllFilters(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'periodo_id',
                'grado_id',
                'turno_id',
                'seccion_id',
                'docente_guia'
            ]);

            $grupos = $this->configGruposService->getGruposByAllFilters($filters);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Grupos filtrados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener grupos filtrados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener registros no sincronizados
     */
    public function unsynced(): JsonResponse
    {
        try {
            $grupos = $this->configGruposService->getUnsyncedRecords();

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Registros no sincronizados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros no sincronizados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Marcar registro como sincronizado
     */
    public function markSynced(string $id): JsonResponse
    {
        try {
            $result = $this->configGruposService->markAsSynced((int) $id);

            return response()->json([
                'success' => true,
                'data' => ['synced' => $result],
                'message' => 'Registro marcado como sincronizado'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como sincronizado: ' . $e->getMessage(),
                'errors' => []
            ], 400);
        }
    }

    /**
     * Obtener registros actualizados después de una fecha
     */
    public function updatedAfter(Request $request): JsonResponse
    {
        try {
            $datetime = $request->get('updated_after');

            if (!$datetime) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro updated_after es requerido',
                    'errors' => []
                ], 400);
            }

            $grupos = $this->configGruposService->getUpdatedAfter($datetime);

            return response()->json([
                'success' => true,
                'data' => $grupos,
                'message' => 'Registros actualizados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros actualizados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener lista de grados disponibles
     */
    public function getGrados(): JsonResponse
    {
        try {
            $grados = $this->configGruposService->getGrados();

            return response()->json([
                'success' => true,
                'data' => $grados,
                'message' => 'Grados obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los grados: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener lista de secciones disponibles
     */
    public function getSecciones(): JsonResponse
    {
        try {
            $secciones = $this->configGruposService->getSecciones();

            return response()->json([
                'success' => true,
                'data' => $secciones,
                'message' => 'Secciones obtenidas exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las secciones: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener lista de docentes guía disponibles
     */
    public function getDocentesGuia(): JsonResponse
    {
        try {
            $docentes = $this->configGruposService->getDocentesGuia();

            return response()->json([
                'success' => true,
                'data' => $docentes,
                'message' => 'Docentes guía obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los docentes guía: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    // Lista de modalidades removida del módulo de grupos

    /**
     * Obtener lista de turnos disponibles
     */
    public function getTurnos(): JsonResponse
    {
        try {
            $turnos = $this->configGruposService->getTurnos();

            return response()->json([
                'success' => true,
                'data' => $turnos,
                'message' => 'Turnos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los turnos: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Obtener lista de períodos lectivos disponibles
     */
    public function getPeriodosLectivos(): JsonResponse
    {
        try {
            $periodosLectivos = $this->configGruposService->getPeriodosLectivos();

            return response()->json([
                'success' => true,
                'data' => $periodosLectivos,
                'message' => 'Períodos lectivos obtenidos exitosamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los períodos lectivos: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }
}
