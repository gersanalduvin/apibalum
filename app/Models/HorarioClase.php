<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class HorarioClase extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'horario_clases';

    protected $fillable = [
        'uuid',
        'periodo_lectivo_id',
        'dia_semana',
        'grupo_id',
        'asignatura_grado_id',
        'titulo_personalizado',
        'docente_id',
        'aula_id',
        'is_fijo',
        'es_simultanea',
        'hora_inicio_real',
        'hora_fin_real',
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
        'is_fijo' => 'boolean',
        'es_simultanea' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer',
    ];

    protected $auditableFields = [
        'dia_semana',
        'grupo_id',
        'asignatura_grado_id',
        'titulo_personalizado',
        'docente_id',
        'aula_id',
        'hora_inicio_real',
        'hora_fin_real'
    ];

    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['updated', 'created', 'deleted'];
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

    public function periodoLectivo()
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id');
    }


    public function grupo()
    {
        return $this->belongsTo(ConfigGrupos::class, 'grupo_id');
    }

    public function asignaturaGrado()
    {
        return $this->belongsTo(NotAsignaturaGrado::class, 'asignatura_grado_id');
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function aula()
    {
        return $this->belongsTo(ConfigAula::class, 'aula_id');
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
        return ['model_name' => 'HorarioClase'];
    }
}
