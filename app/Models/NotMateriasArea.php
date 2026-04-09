<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotMateriasArea extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_materias_areas';

    protected $fillable = [
        'nombre',
        'orden',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function materias()
    {
        return $this->hasMany(NotMateria::class, 'materia_id');
    }
}
