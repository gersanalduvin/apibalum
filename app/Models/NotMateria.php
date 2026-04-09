<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class NotMateria extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'not_materias';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'materia_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function area()
    {
        return $this->belongsTo(NotMateriasArea::class, 'materia_id');
    }
}
