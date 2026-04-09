<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotCalificacionEvidencia extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_calificaciones_evidencias';

    protected $fillable = [
        'uuid',
        'estudiante_id',
        'evidencia_id',
        'evidencia_estudiante_id', // FK a evidencia personalizada (estudiante especial)
        'escala_detalle_id',
        'indicadores_check',
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
        'indicadores_check' => 'array',
        'deleted_at' => 'datetime',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    /** Evidencia general del corte */
    public function evidencia()
    {
        return $this->belongsTo(NotAsignaturaGradoCorteEvidencia::class, 'evidencia_id');
    }

    /** Evidencia personalizada del estudiante especial */
    public function evidenciaEstudiante()
    {
        return $this->belongsTo(NotEvidenciaEstudianteEspecial::class, 'evidencia_estudiante_id');
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
