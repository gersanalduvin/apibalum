<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotTareaEstudiante extends Pivot
{
    use SoftDeletes;
    protected $table = 'not_tarea_estudiantes';

    // Typically pivot tables don't need SoftDeletes unless added to migration. Migration didn't add it.

    protected $fillable = [
        'tarea_id',
        'users_grupo_id'
    ];

    public function tarea()
    {
        return $this->belongsTo(NotTarea::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(UsersGrupo::class, 'users_grupo_id');
    }
}
