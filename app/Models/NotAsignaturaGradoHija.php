<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotAsignaturaGradoHija extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_asignatura_grado_hijas';

    protected $fillable = [
        'uuid',
        'asignatura_grado_id',
        'asignatura_hija_id',
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
    ];

    public function asignatura()
    {
        return $this->belongsTo(NotAsignaturaGrado::class, 'asignatura_grado_id');
    }

    public function hija()
    {
        return $this->belongsTo(NotAsignaturaGrado::class, 'asignatura_hija_id');
    }
}

