<?php

namespace App\Services;

use App\Repositories\CategoriaRepository;
use App\Models\Categoria;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CategoriaService
{
    public function __construct(private CategoriaRepository $categoriaRepository) {}

    /**
     * Obtener todas las categorías
     */
    public function getAllCategories()
    {
        return $this->categoriaRepository->getAll();
    }

    /**
     * Obtener categorías paginadas
     */
    public function getPaginatedCategories(int $perPage = 15, array $filters = [])
    {
        return $this->categoriaRepository->getPaginated($perPage, $filters);
    }

    /**
     * Crear una nueva categoría
     */
    public function createCategory(array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que el código no exista
            if ($this->categoriaRepository->existsByCode($data['codigo'])) {
                throw new Exception('Ya existe una categoría con este código');
            }

            // Validar categoría padre si se especifica
            if (!empty($data['categoria_padre_id'])) {
                $categoriaPadre = $this->categoriaRepository->find($data['categoria_padre_id']);
                if (!$categoriaPadre) {
                    throw new Exception('La categoría padre especificada no existe');
                }
                if (!$categoriaPadre->activo) {
                    throw new Exception('La categoría padre debe estar activa');
                }
            }

            // Agregar usuario que crea
            $data['created_by'] = Auth::id();
            $data['is_synced'] = false;
            $data['version'] = 1;


            $categoria = $this->categoriaRepository->create($data);

            DB::commit();
            return $categoria;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener categoría por ID
     */
    public function getCategoryById(int $id)
    {
        $categoria = $this->categoriaRepository->find($id);

        if (!$categoria) {
            throw new Exception('Categoría no encontrada');
        }

        return $categoria;
    }

    /**
     * Obtener categoría por UUID
     */
    public function getCategoryByUuid(string $uuid)
    {
        $categoria = $this->categoriaRepository->findByUuid($uuid);

        if (!$categoria) {
            throw new Exception('Categoría no encontrada');
        }

        return $categoria;
    }

    /**
     * Actualizar categoría
     */
    public function updateCategory(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $categoria = $this->getCategoryById($id);

            // Validar que el código no exista en otra categoría
            if (isset($data['codigo']) && $this->categoriaRepository->existsByCode($data['codigo'], $id)) {
                throw new Exception('Ya existe otra categoría con este código');
            }

            // Validar categoría padre si se especifica
            if (!empty($data['categoria_padre_id'])) {
                if ($data['categoria_padre_id'] == $id) {
                    throw new Exception('Una categoría no puede ser padre de sí misma');
                }

                $categoriaPadre = $this->categoriaRepository->find($data['categoria_padre_id']);
                if (!$categoriaPadre) {
                    throw new Exception('La categoría padre especificada no existe');
                }
                if (!$categoriaPadre->activo) {
                    throw new Exception('La categoría padre debe estar activa');
                }

                // Verificar que no se cree un ciclo
                if ($this->wouldCreateCycle($id, $data['categoria_padre_id'])) {
                    throw new Exception('Esta asignación crearía un ciclo en la jerarquía');
                }
            }

            // Obtener datos anteriores para auditoría (sin relaciones)
            $datosAnteriores = $categoria->getAttributes();

            // Agregar usuario que actualiza
            $data['updated_by'] = Auth::id();
            $data['is_synced'] = false;
            $data['version'] = $categoria->version + 1;
            $data['updated_locally_at'] = now();

            // La auditoría se maneja automáticamente por el trait Auditable
            $this->categoriaRepository->update($id, $data);

            $categoriaActualizada = $this->getCategoryById($id);

            DB::commit();
            return $categoriaActualizada;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar categoría
     */
    public function deleteCategory(int $id)
    {
        try {
            DB::beginTransaction();

            $categoria = $this->getCategoryById($id);

            if (!$categoria->puedeEliminar()) {
                throw new Exception('No se puede eliminar la categoría porque tiene productos o subcategorías asociadas');
            }

            // Obtener datos anteriores para auditoría
            $datosAnteriores = $categoria->getAttributes();

            // Registrar cambios para auditoría
            $cambiosActuales = $categoria->cambios ?? [];
            $cambiosActuales[] = [
                'accion' => 'eliminado',
                'usuario_email' => Auth::user()->email,
                'fecha' => now()->toDateTimeString(),
                'datos_anteriores' => $datosAnteriores
            ];

            // Marcar como eliminado por el usuario actual y actualizar metadatos
            $this->categoriaRepository->update($id, [
                'deleted_by' => Auth::id(),
                'is_synced' => false,
                'version' => $categoria->version + 1,
                'updated_locally_at' => now(),
                'cambios' => $cambiosActuales
            ]);

            // Soft delete
            $this->categoriaRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener árbol de categorías
     */
    public function getCategoryTree()
    {
        return $this->categoriaRepository->getCategoryTree();
    }

    /**
     * Obtener categorías raíz
     */
    public function getRootCategories()
    {
        return $this->categoriaRepository->getCategoriesRoot();
    }

    /**
     * Obtener categorías hijas
     */
    public function getChildCategories(int $categoriaId)
    {
        return $this->categoriaRepository->getChildCategories($categoriaId);
    }

    // Método getCategoriesByLevel eliminado: el campo 'nivel' fue retirado

    /**
     * Buscar categorías
     */
    public function searchCategories(string $search)
    {
        return $this->categoriaRepository->searchByName($search);
    }

    /**
     * Obtener categorías activas
     */
    public function getActiveCategories()
    {
        return $this->categoriaRepository->getActiveCategories();
    }

    /**
     * Cambiar estado de categoría
     */
    public function toggleCategoryStatus(int $id)
    {
        try {
            DB::beginTransaction();

            $categoria = $this->getCategoryById($id);

            $nuevoEstado = !$categoria->activo;

            // Si se está desactivando, verificar que no tenga subcategorías activas
            if (!$nuevoEstado && $categoria->categoriasHijas()->activas()->count() > 0) {
                throw new Exception('No se puede desactivar la categoría porque tiene subcategorías activas');
            }

            $this->updateCategory($id, ['activo' => $nuevoEstado]);

            DB::commit();
            return $this->getCategoryById($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de categorías
     */
    public function getCategoryStatistics()
    {
        return $this->categoriaRepository->getStatistics();
    }

    /**
     * Sincronizar categorías
     */
    public function syncCategories()
    {
        $categoriasNoSincronizadas = $this->categoriaRepository->getUnsyncedCategories();

        if ($categoriasNoSincronizadas->count() > 0) {
            $ids = $categoriasNoSincronizadas->pluck('id')->toArray();
            $this->categoriaRepository->markAsSynced($ids);
        }

        return [
            'sincronizadas' => $categoriasNoSincronizadas->count(),
            'fecha_sincronizacion' => now()
        ];
    }

    /**
     * Verificar si una asignación de padre crearía un ciclo
     */
    private function wouldCreateCycle(int $categoriaId, int $nuevoPadreId): bool
    {
        $categoria = $this->categoriaRepository->find($nuevoPadreId);

        while ($categoria && $categoria->categoria_padre_id) {
            if ($categoria->categoria_padre_id == $categoriaId) {
                return true;
            }
            $categoria = $this->categoriaRepository->find($categoria->categoria_padre_id);
        }

        return false;
    }

    // Método reorderCategories eliminado: el campo 'orden' fue retirado
}
