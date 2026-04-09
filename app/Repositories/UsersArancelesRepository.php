<?php

namespace App\Repositories;

use App\Models\UsersAranceles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UsersArancelesRepository
{
    public function __construct(private UsersAranceles $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['usuario', 'rubro', 'arancel', 'producto'])->get();
    }

    public function getAllPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['usuario', 'rubro', 'arancel', 'producto']);

        // Aplicar filtros
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('usuario', function ($q) use ($search) {
                $q->where('primer_nombre', 'like', "%{$search}%")
                    ->orWhere('segundo_nombre', 'like', "%{$search}%")
                    ->orWhere('primer_apellido', 'like', "%{$search}%")
                    ->orWhere('segundo_apellido', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['user_id'])) {
            $query->porUsuario($filters['user_id']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['rubro_id'])) {
            $query->porRubro($filters['rubro_id']);
        }

        if (isset($filters['con_recargo']) && $filters['con_recargo']) {
            $query->conRecargo();
        }

        if (isset($filters['con_saldo_pendiente']) && $filters['con_saldo_pendiente']) {
            $query->conSaldoPendiente();
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(array $data): UsersAranceles
    {
        $data['created_by'] = auth()->id();
        return $this->model->create($data);
    }

    public function find(int $id): ?UsersAranceles
    {
        return $this->model->with(['usuario', 'rubro', 'arancel', 'producto', 'recargoAnuladoPor', 'createdBy', 'updatedBy'])->find($id);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_by'] = auth()->id();
        return $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    // Métodos específicos para users_aranceles
    public function findByUserAndRubro(int $userId, int $rubroId): ?UsersAranceles
    {
        return $this->model->where('user_id', $userId)
            ->where('rubro_id', $rubroId)
            ->first();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->with(['rubro', 'arancel', 'producto'])
            ->porUsuario($userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->sortBy(function ($item) {
                return $item->rubro->orden_mes ?? 99;
            })
            ->values();
    }

    public function getPendientesByUser(int $userId): Collection
    {
        return $this->model->with(['rubro', 'arancel', 'producto'])
            ->porUsuario($userId)
            ->pendientes()
            ->leftJoin('config_plan_pago_detalle', 'users_aranceles.rubro_id', '=', 'config_plan_pago_detalle.id')
            ->select('users_aranceles.*')
            ->orderBy('config_plan_pago_detalle.orden_mes', 'asc')
            ->orderBy('users_aranceles.created_at', 'desc')
            ->get();
    }

    public function getConRecargo(): Collection
    {
        return $this->model->with(['usuario', 'rubro'])
            ->conRecargo()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getConSaldoPendiente(): Collection
    {
        return $this->model->with(['usuario', 'rubro'])
            ->conSaldoPendiente()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findMultiple(array $ids): Collection
    {
        return $this->model->with(['usuario', 'rubro', 'arancel', 'producto'])
            ->whereIn('id', $ids)
            ->get();
    }

    public function updateMultiple(array $ids, array $data): int
    {
        $data['updated_by'] = auth()->id();
        return $this->model->whereIn('id', $ids)->update($data);
    }

    public function anularRecargo(array $ids, array $data): int
    {
        $registros = $this->model->whereIn('id', $ids)->get();
        $updated = 0;

        foreach ($registros as $registro) {
            $registro->update([
                'recargo_pagado' => $registro->recargo,
                'saldo_actual' => max(0, $registro->saldo_actual - $registro->recargo),
                'fecha_recargo_anulado' => $data['fecha_recargo_anulado'],
                'recargo_anulado_por' => $data['recargo_anulado_por'],
                'observacion_recargo' => $data['observacion_recargo'],
                'updated_by' => auth()->id()
            ]);
            $updated++;
        }

        return $updated;
    }

    public function exonerar(array $ids, array $data): int
    {
        $registros = $this->model->whereIn('id', $ids)->get();
        $updated = 0;

        foreach ($registros as $registro) {
            $registro->update([
                'estado' => 'exonerado',
                'fecha_exonerado' => $data['fecha_exonerado'],
                'observacion_exonerado' => $data['observacion_exonerado'],
                'saldo_pagado' => $registro->saldo_actual,
                'saldo_actual' => 0,
                'updated_by' => auth()->id()
            ]);
            $updated++;
        }

        return $updated;
    }

    public function aplicarBeca(array $ids, float $beca): int
    {
        $registros = $this->model->whereIn('id', $ids)->get();
        $updated = 0;

        foreach ($registros as $registro) {
            $nuevaBeca = $beca;
            $nuevoImporteTotal = ($registro->importe - $nuevaBeca - $registro->descuento) + $registro->recargo;
            $nuevoSaldoActual = max(0, $nuevoImporteTotal - $registro->saldo_pagado - $registro->recargo_pagado);

            $registro->update([
                'beca' => $nuevaBeca,
                'importe_total' => $nuevoImporteTotal,
                'saldo_actual' => $nuevoSaldoActual,
                'estado' => $nuevoSaldoActual <= 0 ? 'pagado' : 'pendiente',
                'updated_by' => auth()->id()
            ]);
            $updated++;
        }

        return $updated;
    }

    public function aplicarDescuento(array $ids, float $descuento): int
    {
        $registros = $this->model->whereIn('id', $ids)->get();
        $updated = 0;

        foreach ($registros as $registro) {
            $nuevoDescuento = $descuento;
            $nuevoImporteTotal = ($registro->importe - $registro->beca - $nuevoDescuento) + $registro->recargo;
            $nuevoSaldoActual = max(0, $nuevoImporteTotal - $registro->saldo_pagado - $registro->recargo_pagado);

            $registro->update([
                'descuento' => $nuevoDescuento,
                'importe_total' => $nuevoImporteTotal,
                'saldo_actual' => $nuevoSaldoActual,
                'estado' => $nuevoSaldoActual <= 0 ? 'pagado' : 'pendiente',
                'updated_by' => auth()->id()
            ]);
            $updated++;
        }

        return $updated;
    }

    public function aplicarPago(array $ids): int
    {
        // Obtener los registros para calcular los pagos
        $registros = $this->model->whereIn('id', $ids)->get();

        $updateData = [
            'estado' => 'pagado',
            'saldo_actual' => 0,
            'updated_by' => auth()->id()
        ];

        // Actualizar cada registro individualmente para manejar saldo_pagado y recargo_pagado
        $updated = 0;
        foreach ($registros as $registro) {
            $registro->update([
                'saldo_pagado' => $registro->importe_total,
                'recargo_pagado' => $registro->recargo,
                'saldo_actual' => 0,
                'estado' => 'pagado',
                'updated_by' => auth()->id()
            ]);
            $updated++;
        }

        return $updated;
    }

    public function revertirPago(int $id): bool
    {
        $registro = $this->model->find($id);
        if (!$registro) {
            return false;
        }

        return $registro->update([
            'estado' => 'pendiente',
            'saldo_pagado' => 0,
            'recargo_pagado' => 0,
            'saldo_actual' => $registro->importe_total,
            'updated_by' => auth()->id()
        ]);
    }

    public function existeRubroParaUsuario(int $userId, int $rubroId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('rubro_id', $rubroId)
            ->exists();
    }

    public function getEstadisticas(): array
    {
        $total = $this->model->count();
        $pendientes = $this->model->pendientes()->count();
        $pagados = $this->model->pagados()->count();
        $exonerados = $this->model->exonerados()->count();
        $conRecargo = $this->model->conRecargo()->count();
        $conSaldoPendiente = $this->model->conSaldoPendiente()->count();

        $totalImporte = $this->model->sum('importe_total');
        $totalPagado = $this->model->sum('saldo_pagado');
        $totalPendiente = $this->model->sum('saldo_actual');

        return [
            'total_registros' => $total,
            'pendientes' => $pendientes,
            'pagados' => $pagados,
            'exonerados' => $exonerados,
            'con_recargo' => $conRecargo,
            'con_saldo_pendiente' => $conSaldoPendiente,
            'total_importe' => $totalImporte,
            'total_pagado' => $totalPagado,
            'total_pendiente' => $totalPendiente
        ];
    }
}
