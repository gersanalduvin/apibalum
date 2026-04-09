<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class MensajeRespuesta extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'mensaje_id',
        'usuario_id',
        'contenido',
        'reply_to_id',
        'reacciones',
        'menciones',
        'adjuntos'
    ];

    protected $casts = [
        'reacciones' => 'array',
        'menciones' => 'array',
        'adjuntos' => 'array',
    ];

    protected $auditableFields = [
        'contenido'
    ];

    // Relaciones
    public function mensaje()
    {
        return $this->belongsTo(Mensaje::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(MensajeRespuesta::class, 'reply_to_id');
    }

    public function replies()
    {
        return $this->hasMany(MensajeRespuesta::class, 'reply_to_id');
    }
}
