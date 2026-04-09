<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeDestinatario extends Model
{
    use HasFactory;

    protected $fillable = [
        'mensaje_id',
        'user_id',
        'estado',
        'fecha_lectura',
        'ip',
        'user_agent',
        'orden',
        'alumno_id',
    ];

    protected $casts = [
        'fecha_lectura' => 'datetime',
        'orden' => 'integer',
    ];

    // Relaciones
    public function mensaje()
    {
        return $this->belongsTo(Mensaje::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    // Scopes
    public function scopeNoLeidos($query)
    {
        return $query->where('estado', 'no_leido');
    }

    public function scopeLeidos($query)
    {
        return $query->where('estado', 'leido');
    }

    public function scopeParaUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
