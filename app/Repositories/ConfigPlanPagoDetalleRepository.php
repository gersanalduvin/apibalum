<?php

namespace App\Repositories;

use App\Models\ConfigPlanPagoDetalle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigPlanPagoDetalleRepository
{
    public function __construct(private ConfigPlanPagoDetalle $model) {}

    /**
     * Aplica ordenamiento por meses usando el campo numérico orden_mes
     * Los valores null en asociar_mes (orden_mes = 0) aparecen primero
     * Optimizado para evitar problemas de memoria
     */
    private function applyMonthOrdering($query)
    {
        // Usar el campo numérico orden_mes para ordenamiento eficiente
        return $query->orderBy('orden_mes', 'asc');
    }

    public function getAll(): Collection
    {
        $query = $this->model->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo']);
        $query = $this->applyMonthOrdering($query);
        return $query->get();
    }

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo']);
        $query = $this->applyMonthOrdering($query);
        return $query->paginate($perPage);
    }

    public function searchPaginated(string $search, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where(function ($query) use ($search) {
                $query->where('codigo', 'like', "%{$search}%")
                      ->orWhere('nombre', 'like', "%{$search}%")
                      ->orWhereHas('planPago', function ($q) use ($search) {
                          $q->where('nombre', 'like', "%{$search}%");
                      });
            })
            ->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo']);
        
        $query = $this->applyMonthOrdering($query);
        return $query->paginate($perPage);
    }

    public function getByPlanPago(int $planPagoId): Collection
    {
        $query = $this->model->byPlanPago($planPagoId)
            ->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo']);
        
        $query = $this->applyMonthOrdering($query);
        return $query->get();
    }

    public function getByPlanPagoPaginated(int $planPagoId, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->byPlanPago($planPagoId)
            ->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo']);
        
        $query = $this->applyMonthOrdering($query);
        return $query->paginate($perPage);
    }

    public function getByColegiatura(bool $esColegiatura = true): Collection
    {
        return $this->model->byColegiatura($esColegiatura)
            ->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo'])
            ->get();
    }

    public function getByMes(string $mes): Collection
    {
        return $this->model->byMes($mes)
            ->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo'])
            ->get();
    }

    public function getByMoneda(bool $moneda): Collection
    {
        return $this->model->byMoneda($moneda)
            ->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo'])
            ->get();
    }

    public function create(array $data): ConfigPlanPagoDetalle
    {
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigPlanPagoDetalle
    {
        return $this->model->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo', 'createdBy', 'updatedBy'])
            ->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigPlanPagoDetalle
    {
        return $this->model->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo', 'createdBy', 'updatedBy'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function update(int $id, array $data): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        
        return $model->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    public function findByCodigo(string $codigo, int $planPagoId): ?ConfigPlanPagoDetalle
    {
        return $this->model->where('codigo', $codigo)
            ->where('plan_pago_id', $planPagoId)
            ->first();
    }

    public function existsByCodigo(string $codigo, int $planPagoId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('codigo', $codigo)
            ->where('plan_pago_id', $planPagoId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function existsByNombre(string $nombre, int $planPagoId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('nombre', $nombre)
            ->where('plan_pago_id', $planPagoId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function deleteByPlanPago(int $planPagoId): bool
    {
        return $this->model->where('plan_pago_id', $planPagoId)->delete();
    }

    public function searchWithFiltersPaginated(string $search, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where(function ($q) use ($search) {
            $q->where('codigo', 'like', "%{$search}%")
              ->orWhere('nombre', 'like', "%{$search}%")
              ->orWhereHas('planPago', function ($subQ) use ($search) {
                  $subQ->where('nombre', 'like', "%{$search}%");
              });
        });

        if (isset($filters['plan_pago_id'])) {
            $query->where('plan_pago_id', $filters['plan_pago_id']);
        }

        if (isset($filters['es_colegiatura'])) {
            $query->where('es_colegiatura', $filters['es_colegiatura']);
        }

        if (isset($filters['moneda'])) {
            $query->where('moneda', $filters['moneda']);
        }

        if (isset($filters['asociar_mes'])) {
            $query->where('asociar_mes', $filters['asociar_mes']);
        }

        $query = $query->with(['planPago', 'cuentaDebito', 'cuentaCredito', 'cuentaRecargo']);
        $query = $this->applyMonthOrdering($query);
        return $query->paginate($perPage);
    }
}