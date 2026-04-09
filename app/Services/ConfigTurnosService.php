<?php

namespace App\Services;

use App\Repositories\ConfigTurnosRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigTurnosService
{
    public function __construct(private ConfigTurnosRepository $configTurnosRepository) {}

    public function getAllConfigTurnos()
    {
        return $this->configTurnosRepository->getAll();
    }

    public function getAllConfigTurnosPaginated(int $perPage = 15)
    {
        return $this->configTurnosRepository->getAllPaginated($perPage);
    }

    public function createConfigTurnos(array $data)
    {
        try {
            DB::beginTransaction();

            // Verificar si el nombre ya existe
            if ($this->configTurnosRepository->findByName($data['nombre'])) {
                throw new Exception('Ya existe un turno con este nombre');
            }

            // Verificar si el orden ya existe
            if ($this->configTurnosRepository->getByOrden($data['orden'])) {
                throw new Exception('Ya existe un turno con este orden');
            }

            $data['created_by'] = Auth::id();
            $data['version'] = 1;

            $configTurnos = $this->configTurnosRepository->create($data);

            DB::commit();
            return $configTurnos;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigTurnosById(int $id)
    {
        $configTurnos = $this->configTurnosRepository->find($id);

        if (!$configTurnos) {
            throw new Exception('Turno no encontrado');
        }

        return $configTurnos;
    }

    public function getConfigTurnosByUuid(string $uuid)
    {
        $configTurnos = $this->configTurnosRepository->findByUuid($uuid);

        if (!$configTurnos) {
            throw new Exception('Turno no encontrado');
        }

        return $configTurnos;
    }

    public function updateConfigTurnos(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configTurnos = $this->configTurnosRepository->find($id);

            if (!$configTurnos) {
                throw new Exception('Turno no encontrado');
            }

            // Verificar si el nombre ya existe en otro registro
            if (isset($data['nombre'])) {
                $existingTurno = $this->configTurnosRepository->findByName($data['nombre']);
                if ($existingTurno && $existingTurno->id !== $id) {
                    throw new Exception('Ya existe un turno con este nombre');
                }
            }

            // Verificar si el orden ya existe en otro registro
            if (isset($data['orden'])) {
                $existingTurno = $this->configTurnosRepository->getByOrden($data['orden']);
                if ($existingTurno && $existingTurno->id !== $id) {
                    throw new Exception('Ya existe un turno con este orden');
                }
            }

            // Usar solo atributos del modelo para evitar relaciones en auditoría
            $datosAnteriores = $configTurnos->getAttributes();

            $data['updated_by'] = Auth::id();
            $data['version'] = $configTurnos->version + 1;



            $this->configTurnosRepository->update($id, $data);

            DB::commit();
            return $this->configTurnosRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfigTurnos(int $id)
    {
        try {
            DB::beginTransaction();

            $configTurnos = $this->configTurnosRepository->find($id);

            if (!$configTurnos) {
                throw new Exception('Turno no encontrado');
            }

            // Verificar si tiene grupos asociados
            if ($configTurnos->grupos()->count() > 0) {
                throw new Exception('No se puede eliminar el turno porque tiene grupos asociados');
            }



            $this->configTurnosRepository->update($id, [
                'deleted_by' => Auth::id()
            ]);

            $this->configTurnosRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUnsyncedRecords()
    {
        return $this->configTurnosRepository->getUnsyncedRecords();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->configTurnosRepository->markAsSynced($id);
    }

    public function getUpdatedAfter(string $datetime)
    {
        return $this->configTurnosRepository->getUpdatedAfter($datetime);
    }

    public function searchByName(string $search)
    {
        if (empty(trim($search))) {
            throw new Exception('El término de búsqueda no puede estar vacío');
        }

        return $this->configTurnosRepository->searchByName($search);
    }
}
