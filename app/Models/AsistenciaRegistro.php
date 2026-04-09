<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsistenciaRegistro extends Model
{
    use HasFactory;

    protected $fillable = [
        'grupo_id',
        'fecha',
        'corte',
    ];
}
