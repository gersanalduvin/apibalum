<?php

namespace App\Services;

use App\Repositories\ConfPeriodoLectivoRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConfPeriodoLectivoService
{
    public function __construct(private ConfPeriodoLectivoRepository $confPeriodoLectivoRepository) {}

    public function getAllConfPeriodoLectivos()
    {
        return $this->confPeriodoLectivoRepository->getAll();
    }

    public function getPaginatedConfPeriodoLectivos($perPage = 15, $search = null)
    {
        return $this->confPeriodoLectivoRepository->getPaginated($perPage, $search);
    }

    public function createConfPeriodoLectivo(array $data)
    {
        try {
            DB::beginTransaction();

            // Generar UUID si no existe
            if (!isset($data['uuid'])) {
                $data['uuid'] = Str::uuid();
            }

            // Agregar campos de auditoría
            $data['created_by'] = Auth::id();
            $data['is_synced'] = true;
            $data['synced_at'] = now();
            $data['version'] = 1;


            $confPeriodoLectivo = $this->confPeriodoLectivoRepository->create($data);

            DB::commit();
            return $confPeriodoLectivo;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfPeriodoLectivoById($id)
    {
        return $this->confPeriodoLectivoRepository->find($id);
    }

    public function updateConfPeriodoLectivo($id, array $data)
    {
        try {
            DB::beginTransaction();

            $confPeriodoLectivo = $this->confPeriodoLectivoRepository->find($id);
            if (!$confPeriodoLectivo) {
                throw new Exception('Configuración de período lectivo no encontrada');
            }

            // Obtener datos anteriores para el historial (atributos crudos)
            $datosAnteriores = method_exists($confPeriodoLectivo, 'getAttributes') ? $confPeriodoLectivo->getAttributes() : $confPeriodoLectivo->toArray();

            // Agregar campos de auditoría
            $data['updated_by'] = Auth::id();
            $data['is_synced'] = false;
            $data['updated_locally_at'] = now();
            $data['version'] = $confPeriodoLectivo->version + 1;


            $updated = $this->confPeriodoLectivoRepository->update($id, $data);

            if ($updated) {
                $confPeriodoLectivo = $this->confPeriodoLectivoRepository->find($id);
            }

            DB::commit();
            return $confPeriodoLectivo;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfPeriodoLectivo($id)
    {
        try {
            DB::beginTransaction();

            $confPeriodoLectivo = $this->confPeriodoLectivoRepository->find($id);
            if (!$confPeriodoLectivo) {
                throw new Exception('Configuración de período lectivo no encontrada');
            }


            // Actualizar campos antes de eliminar
            $this->confPeriodoLectivoRepository->update($id, [
                'deleted_by' => Auth::id(),
                'is_synced' => false,
                'updated_locally_at' => now(),
                'version' => $confPeriodoLectivo->version + 1
            ]);

            $deleted = $this->confPeriodoLectivoRepository->delete($id);

            DB::commit();
            return $deleted;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Métodos alias con nombres estándar CRUD
    public function create(array $data)
    {
        return $this->createConfPeriodoLectivo($data);
    }

    public function getById($id)
    {
        return $this->getConfPeriodoLectivoById($id);
    }

    public function update($id, array $data)
    {
        return $this->updateConfPeriodoLectivo($id, $data);
    }

    public function delete($id)
    {
        return $this->deleteConfPeriodoLectivo($id);
    }

    // Métodos para sincronización
    public function getUnsyncedConfPeriodoLectivos()
    {
        return $this->confPeriodoLectivoRepository->getUnsynced();
    }

    public function getUpdatedAfter($timestamp)
    {
        return $this->confPeriodoLectivoRepository->getUpdatedAfter($timestamp);
    }

    public function markAsSynced($id)
    {
        return $this->confPeriodoLectivoRepository->markAsSynced($id);
    }

    public function syncFromClient(array $data)
    {
        try {
            DB::beginTransaction();

            foreach ($data as $item) {
                $existing = $this->confPeriodoLectivoRepository->findByUuid($item['uuid']);

                if ($existing) {
                    // Resolver conflictos por versión
                    if ($item['version'] > $existing->version) {
                        $this->confPeriodoLectivoRepository->updateByUuid($item['uuid'], $item);
                    }
                } else {
                    // Crear nuevo registro
                    $this->confPeriodoLectivoRepository->create($item);
                }
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
