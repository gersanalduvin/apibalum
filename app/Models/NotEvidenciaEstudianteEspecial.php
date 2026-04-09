<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class NotEvidenciaEstudianteEspecial extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_evidencias_estudiante_especial';

    protected $fillable = [
        'uuid',
        'estudiante_id',
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
        'indicador'          => 'array',
        'deleted_at'         => 'datetime',
        'synced_at'          => 'datetime',
        'updated_locally_at' => 'datetime',
        'is_synced'          => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relaciones ────────────────────────────────────────────────────────────

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function corteAsignatura()
    {
        return $this->belongsTo(NotAsignaturaGradoCorte::class, 'asignatura_grado_cortes_id');
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

    /** Calificaciones vinculadas a esta evidencia personalizada */
    public function calificaciones()
    {
        return $this->hasMany(NotCalificacionEvidencia::class, 'evidencia_estudiante_id');
    }
}
