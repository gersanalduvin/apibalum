<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotAsignaturaParametro extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_asignatura_parametros';

    protected $fillable = [
        'uuid',
        'asignatura_grado_id',
        'parametro',
        'valor',
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
}

