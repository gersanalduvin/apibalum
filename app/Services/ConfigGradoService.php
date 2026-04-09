<?php

namespace App\Services;

use App\Repositories\ConfigGradoRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigGradoService
{
    public function __construct(private ConfigGradoRepository $configGradoRepository) {}

    public function getAllConfigGrado()
    {
        return $this->configGradoRepository->getAll();
    }

    public function getAllConfigGradoPaginated(int $perPage = 15)
    {
        return $this->configGradoRepository->getAllPaginated($perPage);
    }

    public function createConfigGrado(array $data)
    {
        try {
            DB::beginTransaction();

            // Verificar si el nombre ya existe
            if ($this->configGradoRepository->findByName($data['nombre'])) {
                throw new Exception('Ya existe un grado con este nombre');
            }

            // Verificar si la abreviatura ya existe
            if ($this->configGradoRepository->findByAbreviatura($data['abreviatura'])) {
                throw new Exception('Ya existe un grado con esta abreviatura');
            }

            // Verificar si el orden ya existe
            if ($this->configGradoRepository->getByOrden($data['orden'])) {
                throw new Exception('Ya existe un grado con este orden');
            }

            $data['created_by'] = Auth::id();
            $data['version'] = 1;


            $configGrado = $this->configGradoRepository->create($data);

            DB::commit();
            return $configGrado;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigGradoById(int $id)
    {
        $configGrado = $this->configGradoRepository->find($id);

        if (!$configGrado) {
            throw new Exception('Grado no encontrado');
        }

        return $configGrado;
    }

    public function getConfigGradoByUuid(string $uuid)
    {
        $configGrado = $this->configGradoRepository->findByUuid($uuid);

        if (!$configGrado) {
            throw new Exception('Grado no encontrado');
        }

        return $configGrado;
    }

    public function updateConfigGrado(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configGrado = $this->configGradoRepository->find($id);

            if (!$configGrado) {
                throw new Exception('Grado no encontrado');
            }

            // Verificar si el nombre ya existe en otro registro
            if (isset($data['nombre'])) {
                $existingGrado = $this->configGradoRepository->findByName($data['nombre']);
                if ($existingGrado && $existingGrado->id !== $id) {
                    throw new Exception('Ya existe un grado con este nombre');
                }
            }

            // Verificar si la abreviatura ya existe en otro registro
            if (isset($data['abreviatura'])) {
                $existingGrado = $this->configGradoRepository->findByAbreviatura($data['abreviatura']);
                if ($existingGrado && $existingGrado->id !== $id) {
                    throw new Exception('Ya existe un grado con esta abreviatura');
                }
            }

            // Verificar si el orden ya existe en otro registro
            if (isset($data['orden'])) {
                $existingGrado = $this->configGradoRepository->getByOrden($data['orden']);
                if ($existingGrado && $existingGrado->id !== $id) {
                    throw new Exception('Ya existe un grado con este orden');
                }
            }

            // Evitar incluir relaciones en auditoría
            $datosAnteriores = $configGrado->getAttributes();

            $data['updated_by'] = Auth::id();
            $data['version'] = $configGrado->version + 1;

            // La auditoría se maneja automáticamente por el trait Auditable
            $this->configGradoRepository->update($id, $data);

            DB::commit();
            return $this->configGradoRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfigGrado(int $id)
    {
        try {
            DB::beginTransaction();

            $configGrado = $this->configGradoRepository->find($id);

            if (!$configGrado) {
                throw new Exception('Grado no encontrado');
            }

            // Verificar si tiene grupos asociados
            if ($configGrado->grupos()->count() > 0) {
                throw new Exception('No se puede eliminar el grado porque tiene grupos asociados');
            }

            $cambiosActuales = $configGrado->cambios ?? [];
            $cambiosActuales[] = [
                'accion' => 'eliminado',
                'usuario_email' => Auth::user()->email,
                'fecha' => now()->toDateTimeString(),
                'datos_anteriores' => $configGrado->getAttributes()
            ];

            $this->configGradoRepository->update($id, [
                'deleted_by' => Auth::id(),
                'cambios' => $cambiosActuales
            ]);

            $this->configGradoRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUnsyncedRecords()
    {
        return $this->configGradoRepository->getUnsyncedRecords();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->configGradoRepository->markAsSynced($id);
    }

    public function getUpdatedAfter(string $datetime)
    {
        return $this->configGradoRepository->getUpdatedAfter($datetime);
    }

    public function searchByName(string $search)
    {
        if (empty(trim($search))) {
            throw new Exception('El término de búsqueda no puede estar vacío');
        }

        return $this->configGradoRepository->searchByName($search);
    }
}
