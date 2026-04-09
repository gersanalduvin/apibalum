<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CategoriaRequest;
use App\Services\CategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class CategoriaController extends Controller
{
    public function __construct(private CategoriaService $categoriaService) {}

    /**
     * Obtener todas las categorías paginadas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['activo', 'nivel', 'categoria_padre_id', 'search']);
            
            $categorias = $this->categoriaService->getPaginatedCategories($perPage, $filters);

            return $this->successResponse($categorias, 'Categorías obtenidas exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener las categorías: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener todas las categorías sin paginación
     */
    public function getall(): JsonResponse
    {
        try {
            $categorias = $this->categoriaService->getAllCategories();

            return $this->successResponse($categorias, 'Todas las categorías obtenidas exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener las categorías: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Crear una nueva categoría
     */
    public function store(CategoriaRequest $request): JsonResponse
    {
        try {
            $categoria = $this->categoriaService->createCategory($request->validated());

            return $this->successResponse($categoria, 'Categoría creada exitosamente', 201);

        } catch (Exception $e) {
            return $this->errorResponse('Error al crear la categoría: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Obtener una categoría específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Intentar buscar por ID numérico primero, luego por UUID
            if (is_numeric($id)) {
                $categoria = $this->categoriaService->getCategoryById((int) $id);
            } else {
                $categoria = $this->categoriaService->getCategoryByUuid($id);
            }

            return $this->successResponse($categoria, 'Categoría obtenida exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Categoría no encontrada: ' . $e->getMessage(), [], 404);
        }
    }

    /**
     * Actualizar una categoría
     */
    public function update(CategoriaRequest $request, int $id): JsonResponse
    {
        try {
            $categoria = $this->categoriaService->updateCategory($id, $request->validated());

            return $this->successResponse($categoria, 'Categoría actualizada exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al actualizar la categoría: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Eliminar una categoría
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->categoriaService->deleteCategory($id);

            return $this->successResponse(null, 'Categoría eliminada exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al eliminar la categoría: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Obtener árbol de categorías
     */
    public function tree(): JsonResponse
    {
        try {
            $arbol = $this->categoriaService->getCategoryTree();

            return $this->successResponse($arbol, 'Árbol de categorías obtenido exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener el árbol de categorías: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener categorías raíz
     */
    public function roots(): JsonResponse
    {
        try {
            $categoriasRaiz = $this->categoriaService->getRootCategories();

            return $this->successResponse($categoriasRaiz, 'Categorías raíz obtenidas exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener las categorías raíz: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener categorías hijas de una categoría padre
     */
    public function children(int $categoriaId): JsonResponse
    {
        try {
            $categoriasHijas = $this->categoriaService->getChildCategories($categoriaId);

            return $this->successResponse($categoriasHijas, 'Categorías hijas obtenidas exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener las categorías hijas: ' . $e->getMessage(), [], 500);
        }
    }

    // Método byLevel eliminado: el campo 'nivel' fue retirado

    /**
     * Buscar categorías
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->get('q', '');
            
            if (empty($search)) {
                return $this->errorResponse('El parámetro de búsqueda es requerido', [], 400);
            }

            $categorias = $this->categoriaService->searchCategories($search);

            return $this->successResponse($categorias, 'Búsqueda de categorías completada exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error en la búsqueda de categorías: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener categorías activas
     */
    public function active(): JsonResponse
    {
        try {
            $categoriasActivas = $this->categoriaService->getActiveCategories();

            return $this->successResponse($categoriasActivas, 'Categorías activas obtenidas exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener las categorías activas: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Cambiar estado de una categoría
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $categoria = $this->categoriaService->toggleCategoryStatus($id);

            $estado = $categoria->activo ? 'activada' : 'desactivada';
            return $this->successResponse($categoria, "Categoría {$estado} exitosamente");

        } catch (Exception $e) {
            return $this->errorResponse('Error al cambiar el estado de la categoría: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * Obtener estadísticas de categorías
     */
    public function statistics(): JsonResponse
    {
        try {
            $estadisticas = $this->categoriaService->getCategoryStatistics();

            return $this->successResponse($estadisticas, 'Estadísticas de categorías obtenidas exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener las estadísticas: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Sincronizar categorías
     */
    public function sync(): JsonResponse
    {
        try {
            $resultado = $this->categoriaService->syncCategories();

            return $this->successResponse($resultado, 'Sincronización de categorías completada exitosamente');

        } catch (Exception $e) {
            return $this->errorResponse('Error en la sincronización: ' . $e->getMessage(), [], 500);
        }
    }

    // Método reorder eliminado: el campo 'orden' fue retirado
}