<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\Auditable;

class DailyEvidence extends Model
{
    use SoftDeletes, Auditable, HasUuids;

    protected $table = 'not_evidencias_diarias';

    protected $fillable = [
        'uuid',
        'asignatura_grado_docente_id',
        'corte_id',
        'nombre',
        'descripcion',
        'indicadores',
        'archivos',
        'links',
        'fecha',
        'realizada_en',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'indicadores' => 'array',
        'archivos' => 'array',
        'links' => 'array',
        'fecha' => 'date',
    ];

    public function getArchivosAttribute($value)
    {
        if (!$value) return [];
        $archivos = is_string($value) ? json_decode($value, true) : $value;

        return collect($archivos)->map(function ($file) {
            if (isset($file['path'])) {
                $file['url'] = \Illuminate\Support\Facades\Storage::url($file['path']);
            }
            return $file;
        })->toArray();
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function asignaturaGradoDocente()
    {
        return $this->belongsTo(NotAsignaturaGradoDocente::class, 'asignatura_grado_docente_id');
    }

    public function corte()
    {
        return $this->belongsTo(ConfigNotSemestreParcial::class, 'corte_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(DailyGrade::class, 'evidencia_diaria_id');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(UsersGrupo::class, 'not_evidencia_diaria_estudiantes', 'evidencia_diaria_id', 'users_grupo_id')
            ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
