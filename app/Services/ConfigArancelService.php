<?php

namespace App\Services;

use App\Repositories\ConfigArancelRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfigArancelService
{
    public function __construct(private ConfigArancelRepository $configArancelRepository) {}

    public function getAllAranceles()
    {
        try {
            return $this->configArancelRepository->getAll();
        } catch (Exception $e) {
            Log::error('Error al obtener todos los aranceles: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles');
        }
    }

    public function getAllArancelesPaginated(int $perPage = 15)
    {
        try {
            return $this->configArancelRepository->getAllPaginated($perPage);
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles paginados: ' . $e->getMessage());
            throw new Exception('Error al obtener los aranceles');
        }
    }

    public function createArancel(array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que el código no exista
            if ($this->configArancelRepository->existsByCodigo($data['codigo'])) {
                throw new Exception('Ya existe un arancel con este código');
            }

            // Agregar campos de auditoría para creación
            $data['created_by'] = auth()->id();
            $data['version'] = 1;
            $data['is_synced'] = false;
            $data['updated_locally_at'] = now();


            $arancel = $this->configArancelRepository->create($data);

            if (isset($data['productos']) && is_array($data['productos'])) {
                $syncData = [];
                foreach ($data['productos'] as $prod) {
                    if (!empty($prod['producto_id'])) {
                        $syncData[$prod['producto_id']] = ['cantidad' => $prod['cantidad'] ?? 1];
                    }
                }
                $arancel->productos()->sync($syncData);
            }

            DB::commit();
            return $arancel->load('productos');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear arancel: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getArancelById(int $id)
    {
        try {
            $arancel = $this->configArancelRepository->find($id);

            if (!$arancel) {
                throw new Exception('Arancel no encontrado');
            }

            return $arancel->load('productos');
        } catch (Exception $e) {
            Log::error('Error al obtener arancel por ID: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getArancelByUuid(string $uuid)
    {
        try {
            $arancel = $this->configArancelRepository->findByUuid($uuid);

            if (!$arancel) {
                throw new Exception('Arancel no encontrado');
            }

            return $arancel->load('productos');
        } catch (Exception $e) {
            Log::error('Error al obtener arancel por UUID: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getArancelByCodigo(string $codigo)
    {
        try {
            $arancel = $this->configArancelRepository->findByCodigo($codigo);

            if (!$arancel) {
                throw new Exception('Arancel no encontrado');
            }

            return $arancel->load('productos');
        } catch (Exception $e) {
            Log::error('Error al obtener arancel por código: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateArancel(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $arancel = $this->getArancelById($id);

            // Validar que el código no exista en otro registro
            if (isset($data['codigo']) && $this->configArancelRepository->existsByCodigo($data['codigo'], $id)) {
                throw new Exception('Ya existe otro arancel con este código');
            }

            // Obtener datos anteriores para auditoría
            $datosAnteriores = $arancel->getAttributes();

            // Agregar campos de auditoría para actualización
            $data['updated_by'] = auth()->id();
            $data['version'] = $arancel->version + 1;
            $data['is_synced'] = false;
            $data['updated_locally_at'] = now();

            // Registrar auditoría de actualización
            $updated = $this->configArancelRepository->update($id, $data);

            if (!$updated) {
                throw new Exception('No se pudo actualizar el arancel');
            }

            if (isset($data['productos']) && is_array($data['productos'])) {
                $syncData = [];
                foreach ($data['productos'] as $prod) {
                    if (!empty($prod['producto_id'])) {
                        $syncData[$prod['producto_id']] = ['cantidad' => $prod['cantidad'] ?? 1];
                    }
                }
                $arancel->productos()->sync($syncData);
            }

            DB::commit();
            return $this->getArancelById($id)->load('productos');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar arancel: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateArancelByUuid(string $uuid, array $data)
    {
        try {
            DB::beginTransaction();

            $arancel = $this->getArancelByUuid($uuid);

            // Validar que el código no exista en otro registro
            if (isset($data['codigo']) && $this->configArancelRepository->existsByCodigo($data['codigo'], $arancel->id)) {
                throw new Exception('Ya existe otro arancel con este código');
            }

            // Obtener datos anteriores para auditoría
            $datosAnteriores = $arancel->getAttributes();

            // Agregar campos de auditoría para actualización
            $data['updated_by'] = auth()->id();
            $data['version'] = $arancel->version + 1;
            $data['is_synced'] = false;
            $data['updated_locally_at'] = now();

            // Registrar auditoría de actualización
            $updated = $this->configArancelRepository->updateByUuid($uuid, $data);

            if (!$updated) {
                throw new Exception('No se pudo actualizar el arancel');
            }

            DB::commit();
            return $this->getArancelByUuid($uuid);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar arancel por UUID: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteArancel(int $id)
    {
        try {
            DB::beginTransaction();

            $arancel = $this->getArancelById($id);

            // Obtener datos anteriores para auditoría
            $datosAnteriores = $arancel->getAttributes();

            // La auditoría se maneja automáticamente por el trait Auditable
            $this->configArancelRepository->update($id, [
                'deleted_by' => auth()->id(),
                'updated_locally_at' => now(),
                'is_synced' => false
            ]);

            $deleted = $this->configArancelRepository->delete($id);

            if (!$deleted) {
                throw new Exception('No se pudo eliminar el arancel');
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar arancel: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteArancelByUuid(string $uuid)
    {
        try {
            DB::beginTransaction();

            $arancel = $this->getArancelByUuid($uuid);

            // Obtener datos anteriores para auditoría
            $datosAnteriores = $arancel->getAttributes();

            // La auditoría se maneja automáticamente por el trait Auditable
            $this->configArancelRepository->updateByUuid($uuid, [
                'deleted_by' => auth()->id(),
                'updated_locally_at' => now(),
                'is_synced' => false
            ]);

            $deleted = $this->configArancelRepository->deleteByUuid($uuid);

            if (!$deleted) {
                throw new Exception('No se pudo eliminar el arancel');
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar arancel por UUID: ' . $e->getMessage());
            throw $e;
        }
    }

    public function searchAranceles(array $filters)
    {
        try {
            return $this->configArancelRepository->search($filters);
        } catch (Exception $e) {
            Log::error('Error al buscar aranceles: ' . $e->getMessage());
            throw new Exception('Error al buscar aranceles');
        }
    }

    public function searchArancelesPaginated(array $filters, int $perPage = 15)
    {
        try {
            return $this->configArancelRepository->searchPaginated($filters, $perPage);
        } catch (Exception $e) {
            Log::error('Error al buscar aranceles paginados: ' . $e->getMessage());
            throw new Exception('Error al buscar aranceles');
        }
    }

    public function getActiveAranceles()
    {
        try {
            return $this->configArancelRepository->getActive();
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles activos: ' . $e->getMessage());
            throw new Exception('Error al obtener aranceles activos');
        }
    }

    public function getArancelesByMoneda(bool $moneda)
    {
        try {
            return $this->configArancelRepository->getByMoneda($moneda);
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles por moneda: ' . $e->getMessage());
            throw new Exception('Error al obtener aranceles por moneda');
        }
    }

    public function getNotSyncedAranceles()
    {
        try {
            return $this->configArancelRepository->getNotSynced();
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles no sincronizados: ' . $e->getMessage());
            throw new Exception('Error al obtener aranceles no sincronizados');
        }
    }

    public function getArancelesUpdatedAfter(string $date)
    {
        try {
            return $this->configArancelRepository->getUpdatedAfter($date);
        } catch (Exception $e) {
            Log::error('Error al obtener aranceles actualizados después de fecha: ' . $e->getMessage());
            throw new Exception('Error al obtener aranceles actualizados');
        }
    }

    public function markArancelesAsSynced(array $uuids)
    {
        try {
            DB::beginTransaction();

            $updated = $this->configArancelRepository->markAsSynced($uuids);

            if (!$updated) {
                throw new Exception('No se pudieron marcar los aranceles como sincronizados');
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al marcar aranceles como sincronizados: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getArancelesStats()
    {
        try {
            return [
                'total' => $this->configArancelRepository->count(),
                'activos' => $this->configArancelRepository->countActive(),
                'inactivos' => $this->configArancelRepository->count() - $this->configArancelRepository->countActive(),
                'soles' => $this->configArancelRepository->getByMoneda(true)->count(),
                'dolares' => $this->configArancelRepository->getByMoneda(false)->count(),
                'no_sincronizados' => $this->configArancelRepository->getNotSynced()->count()
            ];
        } catch (Exception $e) {
            Log::error('Error al obtener estadísticas de aranceles: ' . $e->getMessage());
            throw new Exception('Error al obtener estadísticas');
        }
    }

    public function validateArancelData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar código único
        if (isset($data['codigo'])) {
            if ($this->configArancelRepository->existsByCodigo($data['codigo'], $excludeId)) {
                $errors['codigo'] = 'Ya existe un arancel con este código';
            }
        }

        // Validar precio
        if (isset($data['precio']) && $data['precio'] <= 0) {
            $errors['precio'] = 'El precio debe ser mayor a 0';
        }

        return $errors;
    }
}
