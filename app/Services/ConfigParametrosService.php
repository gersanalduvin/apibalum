<?php

namespace App\Services;

use App\Repositories\ConfigParametrosRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigParametrosService
{
    public function __construct(private ConfigParametrosRepository $configParametrosRepository) {}

    public function getAllConfigParametros()
    {
        return $this->configParametrosRepository->getAll();
    }

    public function getAllConfigParametrosPaginated(int $perPage = 15)
    {
        return $this->configParametrosRepository->getAllPaginated($perPage);
    }

    public function getConfigParametros()
    {
        // Obtener el primer registro de parámetros (solo debe haber uno)
        $parametros = $this->configParametrosRepository->getFirst();

        if (!$parametros) {
            // Si no existe, crear uno con valores por defecto
            return $this->createConfigParametros([
                'consecutivo_recibo_oficial' => 1,
                'consecutivo_recibo_interno' => 1,
                'tasa_cambio_dolar' => 0.0000,
                'terminal_separada' => false
            ]);
        }

        return $parametros;
    }

    public function createConfigParametros(array $data)
    {
        try {
            DB::beginTransaction();

            $data['created_by'] = Auth::id();
            $data['version'] = 1;


            $configParametros = $this->configParametrosRepository->create($data);

            DB::commit();
            return $configParametros;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateOrCreateConfigParametros(array $data)
    {
        try {
            DB::beginTransaction();

            $parametros = $this->configParametrosRepository->getFirst();

            if (!$parametros) {
                // Si no existe, crear uno nuevo
                $result = $this->createConfigParametros($data);
                DB::commit();
                return $result;
            }

            // Si existe, actualizarlo
            $datosAnteriores = $parametros->getAttributes();

            $data['updated_by'] = Auth::id();
            $data['version'] = $parametros->version + 1;

            // La auditoría se maneja automáticamente por el trait Auditable
            unset($data['cambios']); // Remover campo obsoleto si viene en los datos
            $this->configParametrosRepository->update($parametros->id, $data);

            DB::commit();
            return $this->configParametrosRepository->find($parametros->id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigParametrosById(int $id)
    {
        $configParametros = $this->configParametrosRepository->find($id);

        if (!$configParametros) {
            throw new Exception('Parámetros no encontrados');
        }

        return $configParametros;
    }

    public function getConfigParametrosByUuid(string $uuid)
    {
        $configParametros = $this->configParametrosRepository->findByUuid($uuid);

        if (!$configParametros) {
            throw new Exception('Parámetros no encontrados');
        }

        return $configParametros;
    }

    public function deleteConfigParametros(int $id)
    {
        try {
            DB::beginTransaction();

            $configParametros = $this->configParametrosRepository->find($id);

            if (!$configParametros) {
                throw new Exception('Parámetros no encontrados');
            }

            $datosAnteriores = $configParametros->getAttributes();

            // Excluir relaciones y campos de auditoría de los datos anteriores
            unset($datosAnteriores['createdBy']);
            unset($datosAnteriores['updatedBy']);
            unset($datosAnteriores['deletedBy']);
            unset($datosAnteriores['cambios']);
            unset($datosAnteriores['created_at']);
            unset($datosAnteriores['updated_at']);
            unset($datosAnteriores['deleted_at']);

            $cambiosActuales = $configParametros->cambios ?? [];
            $cambiosActuales[] = [
                'accion' => 'eliminado',
                'usuario_email' => Auth::user()->email,
                'fecha' => now()->toDateTimeString(),
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => null
            ];

            $this->configParametrosRepository->update($id, [
                'deleted_by' => Auth::id(),
                'cambios' => $cambiosActuales
            ]);

            $this->configParametrosRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUnsyncedRecords()
    {
        return $this->configParametrosRepository->getUnsyncedRecords();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->configParametrosRepository->markAsSynced($id);
    }

    public function getUpdatedAfter(string $datetime)
    {
        return $this->configParametrosRepository->getUpdatedAfter($datetime);
    }
}
