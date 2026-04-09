<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisoDestinatario extends Model
{
    protected $table = 'aviso_destinatarios';

    protected $fillable = [
        'aviso_id',
        'grupo_id',
        'para_todos'
    ];

    public function aviso()
    {
        return $this->belongsTo(Aviso::class, 'aviso_id');
    }

    public function grupo()
    {
        return $this->belongsTo(ConfigGrupo::class, 'grupo_id');
    }
}
