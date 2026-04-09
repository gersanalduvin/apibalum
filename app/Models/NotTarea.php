<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotTarea extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_tareas';

    protected $fillable = [
        'asignatura_grado_docente_id',
        'corte_id',
        'nombre',
        'descripcion',
        'fecha_entrega',
        'puntaje_maximo',
        'evidencia_id',
        'entrega_en_linea',
        'tipo',
        'realizada_en',
        'archivos',
        'links',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'entrega_en_linea' => 'boolean',
        'archivos' => 'array',
        'links' => 'array',
        'puntaje_maximo' => 'decimal:2',
    ];

    public function asignaturaGradoDocente()
    {
        return $this->belongsTo(NotAsignaturaGradoDocente::class, 'asignatura_grado_docente_id');
    }

    public function corte()
    {
        return $this->belongsTo(ConfigNotSemestreParcial::class, 'corte_id');
    }

    public function evidencia()
    {
        return $this->belongsTo(NotAsignaturaGradoCorteEvidencia::class, 'evidencia_id');
    }

    public function estudiantes()
    {
        // Many to Many via pivot not_tarea_estudiantes
        // Since pivot links to users_grupos, and we likely want Users loaded...
        // But let's define the pivot relationship first.
        // Actually, if we want the Users directly, we might need a custom relationship or correct pivot definition.
        // not_tarea_estudiantes: tarea_id, users_grupo_id.
        // users_grupos belongsTo User.
        return $this->belongsToMany(UsersGrupo::class, 'not_tarea_estudiantes', 'tarea_id', 'users_grupo_id')
            ->withTimestamps();
    }

    public function calificaciones()
    {
        return $this->hasMany(NotCalificacionTarea::class, 'tarea_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Accessor to include Storage URLs for archivos
     */
    public function getArchivosAttribute($value)
    {
        $archivos = json_decode($value, true) ?: [];
        return array_map(function ($file) {
            if (isset($file['path'])) {
                $file['url'] = \Illuminate\Support\Facades\Storage::url($file['path']);
            }
            return $file;
        }, $archivos);
    }
}
