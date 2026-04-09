<?php

namespace App\Repositories;

use App\Models\ConfigCatalogoCuentas;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConfigCatalogoCuentasRepository
{
    public function __construct(private ConfigCatalogoCuentas $model) {}

    public function getAll(): Collection
    {
        return $this->model->with(['padre', 'hijos'])->orderBy('codigo')->get();
    }

    public function getAllPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->with(['padre', 'hijos']);

        // Aplicar filtros
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['tipo'])) {
            $query->porTipo($filters['tipo']);
        }

        if (!empty($filters['nivel'])) {
            $query->porNivel($filters['nivel']);
        }

        if (isset($filters['activo']) && $filters['activo'] !== '') {
            if ($filters['activo'] == '1' || $filters['activo'] === true) {
                $query->activos();
            } else {
                $query->where('activo', false);
            }
        }

        if (!empty($filters['padre_id'])) {
            $query->where('padre_id', $filters['padre_id']);
        }

        return $query->orderBy('codigo')->paginate($perPage);
    }

    public function create(array $data): ConfigCatalogoCuentas
    {
        // Calcular el nivel automáticamente basado en el padre
        if (isset($data['padre_id']) && $data['padre_id']) {
            $padre = $this->model->find($data['padre_id']);
            $data['nivel'] = $padre ? $padre->nivel + 1 : 1;
        } else {
            $data['nivel'] = 1;
        }

        $data['created_by'] = auth()->id();
        return $this->model->create($data);
    }

    public function find(int $id): ?ConfigCatalogoCuentas
    {
        return $this->model->with(['padre', 'hijos', 'createdBy', 'updatedBy'])->find($id);
    }

    public function findForAudit(int $id): ?ConfigCatalogoCuentas
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?ConfigCatalogoCuentas
    {
        return $this->model->with(['padre', 'hijos'])->where('uuid', $uuid)->first();
    }

    public function findByCodigo(string $codigo): ?ConfigCatalogoCuentas
    {
        return $this->model->with(['padre', 'hijos'])->where('codigo', $codigo)->first();
    }

    public function update(int $id, array $data): bool
    {
        $cuenta = $this->model->find($id);
        if (!$cuenta) {
            return false;
        }

        // Recalcular nivel si cambió el padre
        if (isset($data['padre_id']) && $data['padre_id'] !== $cuenta->padre_id) {
            if ($data['padre_id']) {
                $padre = $this->model->find($data['padre_id']);
                $data['nivel'] = $padre ? $padre->nivel + 1 : 1;
            } else {
                $data['nivel'] = 1;
            }
        }

        $data['updated_by'] = auth()->id();
        return $cuenta->update($data);
    }

    public function delete(int $id): bool
    {
        $cuenta = $this->model->find($id);
        if (!$cuenta) {
            return false;
        }

        // Verificar que no tenga hijos
        if ($cuenta->hijos()->count() > 0) {
            throw new \Exception('No se puede eliminar una cuenta que tiene cuentas hijas');
        }

        $cuenta->deleted_by = auth()->id();
        $cuenta->save();
        
        return $cuenta->delete();
    }

    // Métodos específicos para catálogo de cuentas
    public function getCuentasPorTipo(string $tipo): Collection
    {
        return $this->model->porTipo($tipo)
            ->activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getCuentasPorNivel(int $nivel): Collection
    {
        return $this->model->porNivel($nivel)
            ->activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getCuentasGrupo(): Collection
    {
        return $this->model->grupos()
            ->activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getCuentasMovimiento(): Collection
    {
        return $this->model->permiteMovimiento()
            ->activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getCuentasPorNaturaleza(string $naturaleza): Collection
    {
        return $this->model->porNaturaleza($naturaleza)
            ->activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getCuentasPorMoneda(bool $esUsd = false): Collection
    {
        $query = $esUsd ? $this->model->monedaUsd() : $this->model->monedaCordobas();
        
        return $query->activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getCuentasHijas(int $padreId): Collection
    {
        return $this->model->where('padre_id', $padreId)
            ->activos()
            ->with(['padre', 'hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getCuentasRaiz(): Collection
    {
        return $this->model->whereNull('padre_id')
            ->activos()
            ->with(['hijos'])
            ->orderBy('codigo')
            ->get();
    }

    public function getArbolCuentas(): Collection
    {
        return $this->model->with(['hijos' => function ($query) {
            $query->activos()->orderBy('codigo');
        }])
        ->whereNull('padre_id')
        ->activos()
        ->orderBy('codigo')
        ->get();
    }

    public function buscarCuentas(string $termino): Collection
    {
        return $this->model->where(function ($query) use ($termino) {
            $query->where('codigo', 'like', "%{$termino}%")
                  ->orWhere('nombre', 'like', "%{$termino}%")
                  ->orWhere('descripcion', 'like', "%{$termino}%");
        })
        ->activos()
        ->with(['padre', 'hijos'])
        ->orderBy('codigo')
        ->get();
    }

    // Métodos para sincronización
    public function getNotSynced(): Collection
    {
        return $this->model->noSincronizados()
            ->with(['padre', 'hijos'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function markAsSynced(string $uuid): bool
    {
        $cuenta = $this->findByUuid($uuid);
        if (!$cuenta) {
            return false;
        }

        return $cuenta->update([
            'is_synced' => true,
            'synced_at' => now(),
            'updated_locally_at' => null
        ]);
    }

    public function getUpdatedAfter(string $date): Collection
    {
        return $this->model->actualizadosDespues($date)
            ->with(['padre', 'hijos'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    // Validaciones específicas
    public function codigoExists(string $codigo, ?int $excludeId = null): bool
    {
        $query = $this->model->where('codigo', $codigo);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    public function puedeSerPadre(int $cuentaId, int $posiblePadreId): bool
    {
        $cuenta = $this->find($cuentaId);
        $posiblePadre = $this->find($posiblePadreId);
        
        if (!$cuenta || !$posiblePadre) {
            return false;
        }

        // No puede ser padre de sí mismo
        if ($cuentaId === $posiblePadreId) {
            return false;
        }

        // No puede ser padre si es descendiente
        $descendientes = $cuenta->getDescendientes();
        return !$descendientes->contains('id', $posiblePadreId);
    }

    public function getEstadisticas(): array
    {
        return [
            'total' => $this->model->count(),
            'activos' => $this->model->activos()->count(),
            'inactivos' => $this->model->inactivos()->count(),
            'grupos' => $this->model->grupos()->count(),
            'movimiento' => $this->model->permiteMovimiento()->count(),
            'por_tipo' => [
                'activo' => $this->model->porTipo('activo')->count(),
                'pasivo' => $this->model->porTipo('pasivo')->count(),
                'patrimonio' => $this->model->porTipo('patrimonio')->count(),
                'ingreso' => $this->model->porTipo('ingreso')->count(),
                'gasto' => $this->model->porTipo('gasto')->count(),
            ],
            'por_naturaleza' => [
                'deudora' => $this->model->porNaturaleza('deudora')->count(),
                'acreedora' => $this->model->porNaturaleza('acreedora')->count(),
            ],
            'por_moneda' => [
                'cordobas' => $this->model->monedaCordobas()->count(),
                'dolares' => $this->model->monedaUsd()->count(),
            ]
        ];
    }
}