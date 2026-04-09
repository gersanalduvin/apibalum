<?php

namespace App\Services;

use App\Repositories\ConfigSeccionRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigSeccionService
{
    public function __construct(private ConfigSeccionRepository $configSeccionRepository) {}

    public function getAllConfigSeccion()
    {
        return $this->configSeccionRepository->getAll();
    }

    public function getAllConfigSeccionPaginated(int $perPage = 15)
    {
        return $this->configSeccionRepository->getAllPaginated($perPage);
    }

    public function createConfigSeccion(array $data)
    {
        try {
            DB::beginTransaction();

            // Verificar si el nombre ya existe
            if ($this->configSeccionRepository->findByName($data['nombre'])) {
                throw new Exception('Ya existe una sección con este nombre');
            }

            // Verificar si el orden ya existe
            if ($this->configSeccionRepository->getByOrden($data['orden'])) {
                throw new Exception('Ya existe una sección con este orden');
            }

            $data['created_by'] = Auth::id();
            $data['version'] = 1;

            $configSeccion = $this->configSeccionRepository->create($data);

            DB::commit();
            return $configSeccion;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigSeccionById(int $id)
    {
        $configSeccion = $this->configSeccionRepository->find($id);

        if (!$configSeccion) {
            throw new Exception('Sección no encontrada');
        }

        return $configSeccion;
    }

    public function getConfigSeccionByUuid(string $uuid)
    {
        $configSeccion = $this->configSeccionRepository->findByUuid($uuid);

        if (!$configSeccion) {
            throw new Exception('Sección no encontrada');
        }

        return $configSeccion;
    }

    public function updateConfigSeccion(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configSeccion = $this->configSeccionRepository->find($id);

            if (!$configSeccion) {
                throw new Exception('Sección no encontrada');
            }

            // Verificar si el nombre ya existe en otro registro
            if (isset($data['nombre'])) {
                $existingSeccion = $this->configSeccionRepository->findByName($data['nombre']);
                if ($existingSeccion && $existingSeccion->id !== $id) {
                    throw new Exception('Ya existe una sección con este nombre');
                }
            }

            // Verificar si el orden ya existe en otro registro
            if (isset($data['orden'])) {
                $existingSeccion = $this->configSeccionRepository->getByOrden($data['orden']);
                if ($existingSeccion && $existingSeccion->id !== $id) {
                    throw new Exception('Ya existe una sección con este orden');
                }
            }

            // Evitar incluir relaciones en auditoría
            $datosAnteriores = $configSeccion->getAttributes();

            $data['updated_by'] = Auth::id();
            $data['version'] = $configSeccion->version + 1;



            $this->configSeccionRepository->update($id, $data);

            DB::commit();
            return $this->configSeccionRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfigSeccion(int $id)
    {
        try {
            DB::beginTransaction();

            $configSeccion = $this->configSeccionRepository->find($id);

            if (!$configSeccion) {
                throw new Exception('Sección no encontrada');
            }

            // Verificar si tiene grupos asociados
            if ($configSeccion->grupos()->count() > 0) {
                throw new Exception('No se puede eliminar la sección porque tiene grupos asociados');
            }



            $this->configSeccionRepository->update($id, [
                'deleted_by' => Auth::id()
            ]);

            $this->configSeccionRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUnsyncedRecords()
    {
        return $this->configSeccionRepository->getUnsyncedRecords();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->configSeccionRepository->markAsSynced($id);
    }

    public function getUpdatedAfter(string $datetime)
    {
        return $this->configSeccionRepository->getUpdatedAfter($datetime);
    }

    public function searchByName(string $search)
    {
        if (empty(trim($search))) {
            throw new Exception('El término de búsqueda no puede estar vacío');
        }

        return $this->configSeccionRepository->searchByName($search);
    }
}
