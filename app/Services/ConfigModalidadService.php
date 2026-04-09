<?php

namespace App\Services;

use App\Repositories\ConfigModalidadRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigModalidadService
{
    public function __construct(private ConfigModalidadRepository $configModalidadRepository) {}

    public function getAllConfigModalidad()
    {
        return $this->configModalidadRepository->getAll();
    }

    public function getAllConfigModalidadPaginated(int $perPage = 15)
    {
        return $this->configModalidadRepository->getAllPaginated($perPage);
    }

    public function createConfigModalidad(array $data)
    {
        try {
            DB::beginTransaction();

            $data['created_by'] = Auth::id();
            $data['version'] = 1;

            $configModalidad = $this->configModalidadRepository->create($data);

            DB::commit();
            return $configModalidad;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigModalidadById(int $id)
    {
        $configModalidad = $this->configModalidadRepository->find($id);

        if (!$configModalidad) {
            throw new Exception('Modalidad no encontrada');
        }

        return $configModalidad;
    }

    public function getConfigModalidadByUuid(string $uuid)
    {
        $configModalidad = $this->configModalidadRepository->findByUuid($uuid);

        if (!$configModalidad) {
            throw new Exception('Modalidad no encontrada');
        }

        return $configModalidad;
    }

    public function updateConfigModalidad(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configModalidad = $this->configModalidadRepository->find($id);

            if (!$configModalidad) {
                throw new Exception('Modalidad no encontrada');
            }

            // Verificar si el nombre ya existe en otro registro
            if (isset($data['nombre'])) {
                $existingModalidad = $this->configModalidadRepository->findByName($data['nombre']);
                if ($existingModalidad && $existingModalidad->id !== $id) {
                    throw new Exception('Ya existe una modalidad con este nombre');
                }
            }

            // Evitar incluir relaciones en auditoría
            $datosAnteriores = $configModalidad->getAttributes();

            $data['updated_by'] = Auth::id();
        $data['version'] = $configModalidad->version + 1;

        // La auditoría se maneja automáticamente por el trait Auditable
        $this->configModalidadRepository->update($id, $data);

            DB::commit();
            return $this->configModalidadRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfigModalidad(int $id)
    {
        try {
            DB::beginTransaction();

            $configModalidad = $this->configModalidadRepository->find($id);

            if (!$configModalidad) {
                throw new Exception('Modalidad no encontrada');
            }

            // Validación de grupos asociados removida: Grupos ya no dependen de modalidad

            $cambiosActuales = $configModalidad->cambios ?? [];
            $cambiosActuales[] = [
                'accion' => 'eliminado',
                'usuario_email' => Auth::user()->email,
                'fecha' => now()->toDateTimeString(),
                'datos_anteriores' => $configModalidad->getAttributes()
            ];

            $this->configModalidadRepository->update($id, [
                'deleted_by' => Auth::id(),
                'cambios' => $cambiosActuales
            ]);

            $this->configModalidadRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUnsyncedRecords()
    {
        return $this->configModalidadRepository->getUnsyncedRecords();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->configModalidadRepository->markAsSynced($id);
    }

    public function getUpdatedAfter(string $datetime)
    {
        return $this->configModalidadRepository->getUpdatedAfter($datetime);
    }

    public function searchByName(string $search)
    {
        if (empty(trim($search))) {
            throw new Exception('El término de búsqueda no puede estar vacío');
        }

        return $this->configModalidadRepository->searchByName($search);
    }
}
