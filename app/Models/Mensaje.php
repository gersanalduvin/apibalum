<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Mensaje extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'remitente_id',
        'asunto',
        'contenido',
        'tipo_mensaje',
        'estado',
        'confirmaciones',
        'plazo_confirmacion',
        'permitir_cambio_respuesta',
        'adjuntos',
    ];

    protected $casts = [
        'confirmaciones' => 'array',
        'adjuntos' => 'array',
        'plazo_confirmacion' => 'datetime',
        'permitir_cambio_respuesta' => 'boolean',
    ];

    protected $auditableFields = [
        'asunto',
        'tipo_mensaje',
        'estado'
    ];

    // Relaciones
    public function remitente()
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    public function respuestas()
    {
        return $this->hasMany(MensajeRespuesta::class)->orderBy('created_at', 'asc');
    }

    // Nueva relación con tabla pivote
    public function destinatariosRelacion()
    {
        return $this->hasMany(MensajeDestinatario::class);
    }

    public function usuariosDestinatarios()
    {
        return $this->belongsToMany(User::class, 'mensaje_destinatarios')
            ->withPivot('estado', 'fecha_lectura', 'ip', 'user_agent', 'orden')
            ->withTimestamps();
    }

    // Accessors - Usando tabla relacional
    public function getTotalDestinatariosAttribute()
    {
        return $this->destinatariosRelacion()->count();
    }

    public function getLeidosCountAttribute()
    {
        return $this->destinatariosRelacion()->where('estado', 'leido')->count();
    }

    public function getNoLeidosCountAttribute()
    {
        return $this->destinatariosRelacion()->where('estado', 'no_leido')->count();
    }

    public function getConfirmacionesSiAttribute()
    {
        return collect($this->confirmaciones)->where('respuesta', 'SI')->count();
    }

    public function getConfirmacionesNoAttribute()
    {
        return collect($this->confirmaciones)->where('respuesta', 'NO')->count();
    }

    // Scopes para filtrado - Optimizados con tabla relacional
    public function scopeEnviados($query, $userId)
    {
        return $query->where('remitente_id', $userId)->where('estado', 'enviado');
    }

    public function scopeRecibidos($query, $userId)
    {
        return $query->whereHas('destinatariosRelacion', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeNoLeidos($query, $userId)
    {
        return $query->whereHas('destinatariosRelacion', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('estado', 'no_leido');
        });
    }

    public function scopeLeidos($query, $userId)
    {
        return $query->whereHas('destinatariosRelacion', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('estado', 'leido');
        });
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_mensaje', $tipo);
    }
}
