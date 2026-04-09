<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotAsignaturaGradoCorteEvidencia extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_asignatura_grado_cortes_evidencias';

    protected $fillable = [
        'uuid',
        'asignatura_grado_cortes_id',
        'evidencia',
        'indicador',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'indicador' => 'array',
    ];

    public function corteAsignatura()
    {
        return $this->belongsTo(NotAsignaturaGradoCorte::class, 'asignatura_grado_cortes_id');
    }
}

