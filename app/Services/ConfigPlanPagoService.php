<?php

namespace App\Services;

use App\Repositories\ConfigPlanPagoRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ConfigPlanPagoService
{
    public function __construct(private ConfigPlanPagoRepository $configPlanPagoRepository) {}

    public function getAllConfigPlanesPago()
    {
        return $this->configPlanPagoRepository->getAll();
    }

    public function getAllConfigPlanesPagoPaginated(int $perPage = 15)
    {
        return $this->configPlanPagoRepository->getAllPaginated($perPage);
    }

    public function getAllActiveConfigPlanesPagoPaginated(int $perPage = 15)
    {
        return $this->configPlanPagoRepository->getAllActivePaginated($perPage);
    }

    public function getAllInactiveConfigPlanesPagoPaginated(int $perPage = 15)
    {
        return $this->configPlanPagoRepository->getAllInactivePaginated($perPage);
    }

    public function searchConfigPlanesPagoPaginated(string $search, int $perPage = 15)
    {
        return $this->configPlanPagoRepository->searchPaginated($search, $perPage);
    }

    public function searchWithFiltersPaginated(string $search, ?bool $estado, ?int $periodoLectivoId = null, int $perPage = 15)
    {
        return $this->configPlanPagoRepository->searchWithFiltersPaginated($search, $estado, $periodoLectivoId, $perPage);
    }

    public function createConfigPlanPago(array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que no exista otro plan con el mismo nombre
            if ($this->configPlanPagoRepository->existsByNombre($data['nombre'])) {
                throw new Exception('Ya existe un plan de pago con este nombre');
            }

            // Agregar información de auditoría
            $data['created_by'] = Auth::id();


            $configPlanPago = $this->configPlanPagoRepository->create($data);

            DB::commit();
            return $configPlanPago;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigPlanPagoById(int $id)
    {
        $configPlanPago = $this->configPlanPagoRepository->find($id);

        if (!$configPlanPago) {
            throw new Exception('Plan de pago no encontrado');
        }

        return $configPlanPago;
    }

    public function getConfigPlanPagoByUuid(string $uuid)
    {
        $configPlanPago = $this->configPlanPagoRepository->findByUuid($uuid);

        if (!$configPlanPago) {
            throw new Exception('Plan de pago no encontrado');
        }

        return $configPlanPago;
    }

    public function updateConfigPlanPago(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configPlanPago = $this->getConfigPlanPagoById($id);

            // Validar que no exista otro plan con el mismo nombre
            if (isset($data['nombre']) && $this->configPlanPagoRepository->existsByNombre($data['nombre'], $id)) {
                throw new Exception('Ya existe otro plan de pago con este nombre');
            }


            $data['updated_by'] = Auth::id();

            $this->configPlanPagoRepository->update($id, $data);

            DB::commit();
            return $this->getConfigPlanPagoById($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfigPlanPago(int $id)
    {
        try {
            DB::beginTransaction();

            $configPlanPago = $this->getConfigPlanPagoById($id);

            // Verificar si tiene detalles asociados
            if ($configPlanPago->detalles()->count() > 0) {
                throw new Exception('No se puede eliminar el plan de pago porque tiene detalles asociados');
            }


            $this->configPlanPagoRepository->update($id, [
                'deleted_by' => Auth::id()

            ]);

            $this->configPlanPagoRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigPlanesPagoByPeriodoLectivo(int $periodoLectivoId)
    {
        return $this->configPlanPagoRepository->getByPeriodoLectivo($periodoLectivoId);
    }

    public function getActiveConfigPlanesPagoByPeriodoLectivo(int $periodoLectivoId)
    {
        return $this->configPlanPagoRepository->getActiveByPeriodoLectivo($periodoLectivoId);
    }

    public function toggleEstado(int $id)
    {
        try {
            DB::beginTransaction();

            $configPlanPago = $this->getConfigPlanPagoById($id);
            $nuevoEstado = !$configPlanPago->estado;

            $this->updateConfigPlanPago($id, [
                'estado' => $nuevoEstado
            ]);

            DB::commit();
            return $this->getConfigPlanPagoById($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
