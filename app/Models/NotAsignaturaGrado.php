<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotAsignaturaGrado extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_asignatura_grado';

    protected $fillable = [
        'uuid',
        'periodo_lectivo_id',
        'grado_id',
        'materia_id',
        'escala_id',
        'nota_aprobar',
        'nota_maxima',
        'incluir_en_promedio',
        'incluir_en_reporte_mined',
        'incluir_horario',
        'incluir_boletin',
        'mostrar_escala',
        'orden',
        'tipo_evaluacion',
        'es_para_educacion_iniciativa',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version',
        'horas_semanales',
        'bloque_continuo',
        'compartida',
        'minutos',
        'permitir_copia',
        'incluir_plan_clase',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'incluir_en_promedio' => 'boolean',
        'incluir_en_reporte_mined' => 'boolean',
        'incluir_horario' => 'boolean',
        'incluir_boletin' => 'boolean',
        'mostrar_escala' => 'boolean',
        'es_para_educacion_iniciativa' => 'boolean',
        'nota_aprobar' => 'integer',
        'nota_maxima' => 'integer',
        'orden' => 'integer',
        'periodo_lectivo_id' => 'integer',
        'grado_id' => 'integer',
        'materia_id' => 'integer',
        'escala_id' => 'integer',
        'compartida' => 'boolean',
        'minutos' => 'integer',
        'permitir_copia' => 'boolean',
        'incluir_plan_clase' => 'boolean',
    ];

    public function periodoLectivo()
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id');
    }

    public function grado()
    {
        return $this->belongsTo(ConfigGrado::class, 'grado_id');
    }

    public function materia()
    {
        return $this->belongsTo(NotMateria::class, 'materia_id');
    }

    public function escala()
    {
        return $this->belongsTo(ConfigNotEscala::class, 'escala_id');
    }

    public function cortes()
    {
        return $this->hasMany(NotAsignaturaGradoCorte::class, 'asignatura_grado_id');
    }

    public function parametros()
    {
        return $this->hasMany(NotAsignaturaParametro::class, 'asignatura_grado_id');
    }

    public function hijas()
    {
        return $this->hasMany(NotAsignaturaGradoHija::class, 'asignatura_grado_id');
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
