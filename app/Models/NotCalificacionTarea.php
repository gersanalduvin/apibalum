<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotCalificacionTarea extends Model
{
    use SoftDeletes, Auditable;

    const STATUS_PENDIENTE = 'pendiente';
    const STATUS_ENTREGADA = 'entregada';
    const STATUS_REVISADA = 'revisada';
    const STATUS_NO_ENTREGADO = 'no_entregado';

    protected $table = 'not_calificaciones_tareas';

    protected $fillable = [
        'tarea_id',
        'estudiante_id',
        'nota',
        'observacion',
        'retroalimentacion',
        'archivos',
        'estado',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'archivos' => 'array'
    ];

    public function tarea()
    {
        return $this->belongsTo(NotTarea::class, 'tarea_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
