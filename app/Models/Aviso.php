<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Aviso extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'avisos';

    protected $fillable = [
        'user_id',
        'titulo',
        'contenido',
        'adjuntos',
        'links',
        'prioridad',
        'fecha_vencimiento',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'adjuntos' => 'array',
        'links' => 'array',
        'fecha_vencimiento' => 'date'
    ];

    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_NORMAL = 'normal';
    const PRIORIDAD_ALTA = 'alta';

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function destinatarios()
    {
        return $this->hasMany(AvisoDestinatario::class, 'aviso_id');
    }

    public function lecturas()
    {
        return $this->hasMany(AvisoLectura::class, 'aviso_id');
    }
}
