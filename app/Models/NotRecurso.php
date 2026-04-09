<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Storage;

class NotRecurso extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_recursos';

    protected $fillable = [
        'uuid',
        'asignatura_grado_docente_id',
        'corte_id',
        'titulo',
        'descripcion',
        'tipo', // archivo, enlace
        'contenido', // path or url
        'publicado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'publicado' => 'boolean',
        'corte_id' => 'integer',
        'asignatura_grado_docente_id' => 'integer',
    ];

    protected $appends = ['full_url'];

    public function asignaturaGradoDocente()
    {
        return $this->belongsTo(NotAsignaturaGradoDocente::class, 'asignatura_grado_docente_id');
    }

    public function corte()
    {
        return $this->belongsTo(ConfigNotSemestreParcial::class, 'corte_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archivos()
    {
        return $this->hasMany(NotRecursoArchivo::class, 'not_recurso_id');
    }

    /**
     * Accessor for full URL if type is archivo
     */
    public function getFullUrlAttribute()
    {
        if ($this->tipo === 'archivo' && $this->contenido) {
            return Storage::url($this->contenido);
        }
        return $this->contenido;
    }
}
