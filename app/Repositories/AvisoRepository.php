<?php

namespace App\Repositories;

use App\Models\Aviso;
use App\Models\AvisoLectura;
use App\Models\AvisoDestinatario;
use App\Repositories\Contracts\AvisoRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AvisoRepository implements AvisoRepositoryInterface
{
    protected $model;

    public function __construct(Aviso $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = [])
    {
        $userId = auth()->id();
        $query = $this->model->with(['user', 'destinatarios.grupo'])
            ->withExists(['lecturas as leido_por_mi' => function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            }]);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function findById(int $id)
    {
        return $this->model->with(['user', 'destinatarios.grupo'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $aviso = $this->model->create([
                'user_id' => $data['user_id'],
                'titulo' => $data['titulo'],
                'contenido' => $data['contenido'],
                'adjuntos' => $data['adjuntos'] ?? [],
                'links' => $data['links'] ?? [],
                'prioridad' => $data['prioridad'] ?? 'normal',
                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Crear destinatarios
            if (!empty($data['grupos'])) {
                foreach ($data['grupos'] as $grupoId) {
                    $aviso->destinatarios()->create(['grupo_id' => $grupoId]);
                }
            } elseif (isset($data['para_todos']) && $data['para_todos']) {
                $aviso->destinatarios()->create(['para_todos' => true]);
            }

            return $aviso;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $aviso = $this->findById($id);

            $aviso->update([
                'titulo' => $data['titulo'] ?? $aviso->titulo,
                'contenido' => $data['contenido'] ?? $aviso->contenido,
                'adjuntos' => $data['adjuntos'] ?? $aviso->adjuntos,
                'links' => $data['links'] ?? $aviso->links,
                'prioridad' => $data['prioridad'] ?? $aviso->prioridad,
                'fecha_vencimiento' => $data['fecha_vencimiento'] ?? $aviso->fecha_vencimiento,
                'updated_by' => $data['updated_by'] ?? null,
            ]);

            if (isset($data['grupos']) || isset($data['para_todos'])) {
                // Borrar destinatarios viejos
                $aviso->destinatarios()->delete();

                // Crear nuevos
                if (!empty($data['grupos']) && (!isset($data['para_todos']) || !$data['para_todos'])) {
                    foreach ($data['grupos'] as $grupoId) {
                        $aviso->destinatarios()->create(['grupo_id' => $grupoId]);
                    }
                } elseif (isset($data['para_todos']) && $data['para_todos']) {
                    $aviso->destinatarios()->create(['para_todos' => true]);
                }
            }

            return $aviso;
        });
    }

    public function delete(int $id)
    {
        $aviso = $this->findById($id);
        return $aviso->delete();
    }

    public function getByGroups(array $groupIds, array $filters = [])
    {
        $userId = auth()->id();
        $query = $this->model->whereHas('destinatarios', function ($q) use ($groupIds) {
            if (!empty($groupIds)) {
                $q->whereIn('grupo_id', $groupIds)
                    ->orWhere('para_todos', true);
            } else {
                $q->where('para_todos', true);
            }
        })
            ->with(['user'])
            ->withExists(['lecturas as leido_por_mi' => function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            }]);

        return $query->orderBy('created_at', 'desc')->get();
    }
}
