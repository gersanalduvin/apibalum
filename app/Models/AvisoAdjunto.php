<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisoAdjunto extends Model
{
    protected $table = 'aviso_adjuntos';

    protected $fillable = [
        'aviso_id',
        'nombre_original',
        's3_key',
        'tipo_mime',
        'size'
    ];

    public function aviso()
    {
        return $this->belongsTo(Aviso::class, 'aviso_id');
    }
}
