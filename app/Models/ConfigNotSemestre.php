<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class ConfigNotSemestre extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'config_not_semestre';

    protected $fillable = [
        'uuid',
        'nombre',
        'abreviatura',
        'orden',
        'periodo_lectivo_id',
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
    ];

    public function periodoLectivo()
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id');
    }

    public function parciales()
    {
        return $this->hasMany(ConfigNotSemestreParcial::class, 'semestre_id');
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

