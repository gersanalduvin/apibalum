<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotCalificacion extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_calificaciones';

    protected $fillable = [
        'uuid',
        'corte_id',
        'estudiante_id',
        'asignatura_grado_id',
        'nota',
        'escala_detalle_id',
        'observacion',
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
        'nota' => 'decimal:2',
    ];

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function corte()
    {
        return $this->belongsTo(ConfigNotSemestreParcial::class, 'corte_id');
    }

    public function asignatura()
    {
        return $this->belongsTo(NotAsignaturaGrado::class, 'asignatura_grado_id');
    }

    public function escalaDetalle()
    {
        return $this->belongsTo(ConfigNotEscalaDetalle::class, 'escala_detalle_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
