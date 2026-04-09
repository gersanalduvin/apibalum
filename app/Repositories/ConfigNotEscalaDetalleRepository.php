<?php

namespace App\Repositories;

use App\Models\ConfigNotEscalaDetalle;
use Illuminate\Database\Eloquent\Collection;

class ConfigNotEscalaDetalleRepository
{
    public function __construct(private ConfigNotEscalaDetalle $model) {}

    public function byEscala(int $escalaId): Collection
    {
        return $this->model->where('escala_id', $escalaId)
            ->orderBy('orden')
            ->orderBy('rango_inicio')
            ->get();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->destroy($id);
    }
}

