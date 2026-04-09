<?php

namespace App\Services;

use App\Repositories\ConfigFormaPagoRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfigFormaPagoService
{
    public function __construct(private ConfigFormaPagoRepository $configFormaPagoRepository) {}

    public function getAllConfigFormasPago()
    {
        return $this->configFormaPagoRepository->getAll();
    }

    public function getAllConfigFormasPagoPaginated(int $perPage = 15)
    {
        return $this->configFormaPagoRepository->getAllPaginated($perPage);
    }

    public function getAllActiveConfigFormasPago()
    {
        return $this->configFormaPagoRepository->getAllActive();
    }

    public function getAllActiveConfigFormasPagoPaginated(int $perPage = 15)
    {
        return $this->configFormaPagoRepository->getAllActivePaginated($perPage);
    }

    public function getAllByEfectivo(bool $efectivo)
    {
        return $this->configFormaPagoRepository->getAllByEfectivo($efectivo);
    }

    public function getAllByEfectivoPaginated(bool $efectivo, int $perPage = 15)
    {
        return $this->configFormaPagoRepository->getAllByEfectivoPaginated($efectivo, $perPage);
    }

    public function getAllInactiveConfigFormasPagoPaginated(int $perPage = 15)
    {
        return $this->configFormaPagoRepository->getAllInactivePaginated($perPage);
    }

    public function searchWithFiltersPaginated(array $filters, int $perPage = 15)
    {
        return $this->configFormaPagoRepository->searchWithFiltersPaginated($filters, $perPage);
    }

    public function createConfigFormaPago(array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que no exista el nombre
            if ($this->configFormaPagoRepository->existsByNombre($data['nombre'])) {
                throw new Exception('Ya existe una forma de pago con este nombre');
            }

            // Validar que no exista la abreviatura
            if ($this->configFormaPagoRepository->existsByAbreviatura($data['abreviatura'])) {
                throw new Exception('Ya existe una forma de pago con esta abreviatura');
            }

            $data['created_by'] = Auth::id();
            $data['version'] = 1;


            $configFormaPago = $this->configFormaPagoRepository->create($data);

            DB::commit();
            return $configFormaPago;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateConfigFormaPago(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configFormaPago = $this->configFormaPagoRepository->find($id);

            if (!$configFormaPago) {
                throw new Exception('Forma de pago no encontrada');
            }

            // Validar que no exista el nombre (excluyendo el actual)
            if (isset($data['nombre']) && $this->configFormaPagoRepository->existsByNombre($data['nombre'], $id)) {
                throw new Exception('Ya existe una forma de pago con este nombre');
            }

            // Validar que no exista la abreviatura (excluyendo el actual)
            if (isset($data['abreviatura']) && $this->configFormaPagoRepository->existsByAbreviatura($data['abreviatura'], $id)) {
                throw new Exception('Ya existe una forma de pago con esta abreviatura');
            }

            $datosAnteriores = $configFormaPago->getAttributes();

            $data['updated_by'] = Auth::id();
            $data['version'] = $configFormaPago->version + 1;

            // La auditoría se maneja automáticamente por el trait Auditable
            unset($data['cambios']); // Remover campo obsoleto si viene en los datos
            $this->configFormaPagoRepository->update($id, $data);

            DB::commit();
            return $this->configFormaPagoRepository->find($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigFormaPagoById(int $id)
    {
        $configFormaPago = $this->configFormaPagoRepository->find($id);

        if (!$configFormaPago) {
            throw new Exception('Forma de pago no encontrada');
        }

        return $configFormaPago;
    }

    public function getConfigFormaPagoByUuid(string $uuid)
    {
        $configFormaPago = $this->configFormaPagoRepository->findByUuid($uuid);

        if (!$configFormaPago) {
            throw new Exception('Forma de pago no encontrada');
        }

        return $configFormaPago;
    }

    public function deleteConfigFormaPago(int $id)
    {
        try {
            DB::beginTransaction();

            $configFormaPago = $this->configFormaPagoRepository->find($id);

            if (!$configFormaPago) {
                throw new Exception('Forma de pago no encontrada');
            }

            $datosAnteriores = $configFormaPago->getAttributes();

            // Excluir relaciones y campos de auditoría de los datos anteriores
            unset($datosAnteriores['createdBy']);
            unset($datosAnteriores['updatedBy']);
            unset($datosAnteriores['deletedBy']);
            unset($datosAnteriores['cambios']);
            unset($datosAnteriores['created_at']);
            unset($datosAnteriores['updated_at']);
            unset($datosAnteriores['deleted_at']);

            $cambiosActuales = $configFormaPago->cambios ?? [];
            $cambiosActuales[] = [
                'accion' => 'eliminado',
                'usuario_email' => Auth::user()->email,
                'fecha' => now()->toDateTimeString(),
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => null
            ];

            $this->configFormaPagoRepository->update($id, [
                'deleted_by' => Auth::id(),
                'cambios' => $cambiosActuales
            ]);

            $this->configFormaPagoRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function searchConfigFormasPago(string $term)
    {
        return $this->configFormaPagoRepository->search($term);
    }

    public function searchConfigFormasPagoPaginated(string $term, int $perPage = 15)
    {
        return $this->configFormaPagoRepository->searchPaginated($term, $perPage);
    }

    public function getUnsyncedRecords()
    {
        return $this->configFormaPagoRepository->getUnsyncedRecords();
    }

    public function markAsSynced(int $id): bool
    {
        return $this->configFormaPagoRepository->markAsSynced($id);
    }

    public function getUpdatedAfter(string $datetime)
    {
        return $this->configFormaPagoRepository->getUpdatedAfter($datetime);
    }
}
