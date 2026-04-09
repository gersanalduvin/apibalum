<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'asistencias';

    public const CORTES = ['corte_1', 'corte_2', 'corte_3', 'corte_4'];
    public const ESTADOS = ['ausencia_justificada', 'ausencia_injustificada', 'tarde_justificada', 'tarde_injustificada', 'permiso', 'suspendido'];

    protected $fillable = [
        'user_id',
        'grupo_id',
        'fecha',
        'corte',
        'estado',
        'justificacion',
        'hora_registro',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'fecha' => 'date',
        'deleted_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(ConfigGrupos::class, 'grupo_id');
    }
}
