<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class ConfigNotSemestreParcial extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'config_not_semestre_parciales';

    protected $fillable = [
        'uuid',
        'semestre_id',
        'nombre',
        'abreviatura',
        'fecha_inicio_corte',
        'fecha_fin_corte',
        'fecha_inicio_publicacion_notas',
        'fecha_fin_publicacion_notas',
        'orden',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'fecha_inicio_corte' => 'date',
        'fecha_fin_corte' => 'date',
        'fecha_inicio_publicacion_notas' => 'date',
        'fecha_fin_publicacion_notas' => 'date',
    ];

    public function semestre()
    {
        return $this->belongsTo(ConfigNotSemestre::class, 'semestre_id');
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

