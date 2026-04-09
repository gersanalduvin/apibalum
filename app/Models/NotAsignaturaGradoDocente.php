<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotAsignaturaGradoDocente extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_asignatura_grado_docente';

    protected $fillable = [
        'user_id',
        'asignatura_grado_id',
        'grupo_id',
        'permiso_fecha_corte1',
        'permiso_fecha_corte2',
        'permiso_fecha_corte3',
        'permiso_fecha_corte4',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'permiso_fecha_corte1' => 'datetime',
        'permiso_fecha_corte2' => 'datetime',
        'permiso_fecha_corte3' => 'datetime',
        'permiso_fecha_corte4' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asignaturaGrado()
    {
        return $this->belongsTo(NotAsignaturaGrado::class, 'asignatura_grado_id');
    }

    public function grupo()
    {
        return $this->belongsTo(ConfigGrupo::class, 'grupo_id');
    }
}
