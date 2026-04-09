<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class ConfigGrupo extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'config_grupos';

    protected $fillable = [
        'uuid',
        'grado_id',
        'seccion_id',
        'turno_id',
        'docente_guia',
        'periodo_lectivo_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $appends = ['nombre'];

    public function getNombreAttribute()
    {
        $grado = $this->grado ? $this->grado->nombre : '';
        $seccion = $this->seccion ? $this->seccion->nombre : '';
        return "{$grado} - {$seccion}";
    }

    protected $casts = [
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer',
        'grado_id' => 'integer',
        'seccion_id' => 'integer',
        'turno_id' => 'integer',
        'docente_guia' => 'integer',
        'periodo_lectivo_id' => 'integer'
    ];

    protected $dates = [
        'deleted_at',
        'synced_at',
        'updated_locally_at'
    ];

    // Configuración del trait Auditable
    protected $auditableFields = ['grado_id', 'seccion_id', 'turno_id', 'docente_guia', 'periodo_lectivo_id'];
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

    // Relaciones principales
    public function grado()
    {
        return $this->belongsTo(ConfigGrado::class, 'grado_id');
    }

    public function seccion()
    {
        return $this->belongsTo(ConfigSeccion::class, 'seccion_id');
    }

    public function turno()
    {
        return $this->belongsTo(ConfigTurnos::class, 'turno_id');
    }

    // Relación de modalidad removida: el modelo ya no referencia modalidad_id

    public function docenteGuia()
    {
        return $this->belongsTo(User::class, 'docente_guia');
    }

    public function periodoLectivo()
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id');
    }

    // Relaciones de auditoría
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

    // Relación inversa con usuarios
    public function usersGrupos()
    {
        return $this->hasMany(UsersGrupo::class, 'grupo_id');
    }

    public function usuarios()
    {
        return $this->hasManyThrough(User::class, UsersGrupo::class, 'grupo_id', 'id', 'id', 'user_id');
    }

    /**
     * Relación: Asignaturas-Docentes asignados a este grupo
     */
    public function asignaturasDocente()
    {
        return $this->hasMany(NotAsignaturaGradoDocente::class, 'grupo_id');
    }

    // Scopes
    public function scopeByGrado($query, $gradoId)
    {
        return $query->where('grado_id', $gradoId);
    }

    public function scopeBySeccion($query, $seccionId)
    {
        return $query->where('seccion_id', $seccionId);
    }

    public function scopeByTurno($query, $turnoId)
    {
        return $query->where('turno_id', $turnoId);
    }

    // Scope por modalidad removido

    public function scopeByPeriodoLectivo($query, $periodoLectivoId)
    {
        return $query->where('periodo_lectivo_id', $periodoLectivoId);
    }

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'ConfigGrupo'
        ];
    }

    /**
     * @deprecated Usar getRecentAudits() del trait Auditable
     */
    public function getHistorialCambios()
    {
        return $this->cambios ?? [];
    }
}
