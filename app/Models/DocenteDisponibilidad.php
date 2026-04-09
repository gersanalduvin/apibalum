<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class DocenteDisponibilidad extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'docente_disponibilidad';

    protected $fillable = [
        'uuid',
        'docente_id',
        'turno_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'titulo',
        'disponible',
        'motivo',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'disponible' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer',
    ];

    protected $auditableFields = [
        'dia_semana',
        'disponible',
        'motivo'
    ];

    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function turno()
    {
        return $this->belongsTo(ConfigTurnos::class, 'turno_id');
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

    public function getCustomAuditMetadata(): array
    {
        return ['model_name' => 'DocenteDisponibilidad'];
    }
}
