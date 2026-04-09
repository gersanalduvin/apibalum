<?php

namespace App\Services;

use App\Repositories\ConfigPlanPagoDetalleRepository;
use App\Services\ConfigPlanPagoService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ConfigPlanPagoDetalleService
{
    public function __construct(
        private ConfigPlanPagoDetalleRepository $configPlanPagoDetalleRepository,
        private ConfigPlanPagoService $configPlanPagoService
    ) {}

    public function getAllConfigPlanPagoDetalles()
    {
        return $this->configPlanPagoDetalleRepository->getAll();
    }

    public function getAllConfigPlanPagoDetallesPaginated(int $perPage = 15)
    {
        return $this->configPlanPagoDetalleRepository->getAllPaginated($perPage);
    }

    public function searchConfigPlanPagoDetallesPaginated(string $search, int $perPage = 15)
    {
        return $this->configPlanPagoDetalleRepository->searchPaginated($search, $perPage);
    }

    public function searchWithFiltersPaginated(string $search, array $filters, int $perPage = 15)
    {
        return $this->configPlanPagoDetalleRepository->searchWithFiltersPaginated($search, $filters, $perPage);
    }

    public function getDetallesByPlanPago(int $planPagoId)
    {
        return $this->configPlanPagoDetalleRepository->getByPlanPago($planPagoId);
    }

    public function getDetallesByPlanPagoPaginated(int $planPagoId, int $perPage = 15)
    {
        return $this->configPlanPagoDetalleRepository->getByPlanPagoPaginated($planPagoId, $perPage);
    }

    public function getPlanPagoWithDetallesPaginated(int $planPagoId, int $perPage = 15)
    {
        // Obtener la información del plan de pago
        $planPago = $this->configPlanPagoService->getConfigPlanPagoById($planPagoId);
        
        // Obtener los detalles paginados
        $detallesPaginados = $this->configPlanPagoDetalleRepository->getByPlanPagoPaginated($planPagoId, $perPage);
        
        return [
            'plan_pago' => $planPago,
            'detalles' => $detallesPaginados
        ];
    }

    public function getDetallesByColegiatura(bool $esColegiatura = true)
    {
        return $this->configPlanPagoDetalleRepository->getByColegiatura($esColegiatura);
    }

    public function getDetallesByMes(string $mes)
    {
        return $this->configPlanPagoDetalleRepository->getByMes($mes);
    }

    public function getDetallesByMoneda(bool $moneda)
    {
        return $this->configPlanPagoDetalleRepository->getByMoneda($moneda);
    }

    public function createConfigPlanPagoDetalle(array $data)
    {
        try {
            DB::beginTransaction();

            // Validar que no exista otro detalle con el mismo código en el mismo plan
            if ($this->configPlanPagoDetalleRepository->existsByCodigo($data['codigo'], $data['plan_pago_id'])) {
                throw new Exception('Ya existe un detalle con este código en el plan de pago');
            }

            // Validar que no exista otro detalle con el mismo nombre en el mismo plan
            if ($this->configPlanPagoDetalleRepository->existsByNombre($data['nombre'], $data['plan_pago_id'])) {
                throw new Exception('Ya existe un detalle con este nombre en el plan de pago');
            }

            // Agregar información de auditoría
            $data['created_by'] = Auth::id();

            $configPlanPagoDetalle = $this->configPlanPagoDetalleRepository->create($data);

            DB::commit();
            return $configPlanPagoDetalle;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigPlanPagoDetalleById(int $id)
    {
        $configPlanPagoDetalle = $this->configPlanPagoDetalleRepository->find($id);

        if (!$configPlanPagoDetalle) {
            throw new Exception('Detalle de plan de pago no encontrado');
        }

        return $configPlanPagoDetalle;
    }

    public function getConfigPlanPagoDetalleByUuid(string $uuid)
    {
        $configPlanPagoDetalle = $this->configPlanPagoDetalleRepository->findByUuid($uuid);

        if (!$configPlanPagoDetalle) {
            throw new Exception('Detalle de plan de pago no encontrado');
        }

        return $configPlanPagoDetalle;
    }

    public function updateConfigPlanPagoDetalle(int $id, array $data)
    {
        try {
            DB::beginTransaction();

            $configPlanPagoDetalle = $this->getConfigPlanPagoDetalleById($id);

            // Validar que no exista otro detalle con el mismo código en el mismo plan
            if (isset($data['codigo']) && $this->configPlanPagoDetalleRepository->existsByCodigo($data['codigo'], $configPlanPagoDetalle->plan_pago_id, $id)) {
                throw new Exception('Ya existe otro detalle con este código en el plan de pago');
            }

            // Validar que no exista otro detalle con el mismo nombre en el mismo plan
            if (isset($data['nombre']) && $this->configPlanPagoDetalleRepository->existsByNombre($data['nombre'], $configPlanPagoDetalle->plan_pago_id, $id)) {
                throw new Exception('Ya existe otro detalle con este nombre en el plan de pago');
            }


            $data['updated_by'] = Auth::id();


            $this->configPlanPagoDetalleRepository->update($id, $data);

            DB::commit();
            return $this->getConfigPlanPagoDetalleById($id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteConfigPlanPagoDetalle(int $id)
    {
        try {
            DB::beginTransaction();

            $configPlanPagoDetalle = $this->getConfigPlanPagoDetalleById($id);


            $this->configPlanPagoDetalleRepository->delete($id);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteDetallesByPlanPago(int $planPagoId)
    {
        try {
            DB::beginTransaction();

            $this->configPlanPagoDetalleRepository->deleteByPlanPago($planPagoId);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createMultipleDetalles(int $planPagoId, array $detalles)
    {
        try {
            DB::beginTransaction();

            $detallesCreados = [];

            foreach ($detalles as $detalle) {
                $detalle['plan_pago_id'] = $planPagoId;
                $detallesCreados[] = $this->createConfigPlanPagoDetalle($detalle);
            }

            DB::commit();
            return $detallesCreados;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function duplicateDetallesFromPlan(int $planOrigenId, int $planDestinoId)
    {
        try {
            DB::beginTransaction();

            $detallesOrigen = $this->getDetallesByPlanPago($planOrigenId);
            $detallesCreados = [];

            foreach ($detallesOrigen as $detalle) {
                $nuevoDetalle = $detalle->toArray();

                // Remover campos que no deben duplicarse
                unset($nuevoDetalle['id'], $nuevoDetalle['uuid'], $nuevoDetalle['created_at'],
                      $nuevoDetalle['updated_at'], $nuevoDetalle['deleted_at']);

                $nuevoDetalle['plan_pago_id'] = $planDestinoId;
                $nuevoDetalle['codigo'] = $nuevoDetalle['codigo'] . '_COPY';

                $detallesCreados[] = $this->createConfigPlanPagoDetalle($nuevoDetalle);
            }

            DB::commit();
            return $detallesCreados;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
