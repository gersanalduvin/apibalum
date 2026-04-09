<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class UsersAranceles extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'users_aranceles';

    protected $fillable = [
        'rubro_id',
        'user_id',
        'aranceles_id',
        'producto_id',
        'importe',
        'beca',
        'descuento',
        'importe_total',
        'recargo',
        'saldo_pagado',
        'recargo_pagado',
        'saldo_actual',
        'estado',
        'fecha_exonerado',
        'observacion_exonerado',
        'fecha_recargo_anulado',
        'recargo_anulado_por',
        'observacion_recargo',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'beca' => 'decimal:2',
        'descuento' => 'decimal:2',
        'importe_total' => 'decimal:2',
        'recargo' => 'decimal:2',
        'saldo_pagado' => 'decimal:2',
        'recargo_pagado' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'fecha_exonerado' => 'date',
        'fecha_recargo_anulado' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $appends = ['fecha_vencimiento'];

    public function getFechaVencimientoAttribute()
    {
        return $this->rubro?->fecha_vencimiento?->toDateString();
    }

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    // Configuración del trait Auditable
    protected $auditableFields = [
        'rubro_id',
        'user_id',
        'aranceles_id',
        'producto_id',
        'importe',
        'beca',
        'descuento',
        'importe_total',
        'recargo',
        'saldo_pagado',
        'recargo_pagado',
        'saldo_actual',
        'estado',
        'fecha_exonerado',
        'observacion_exonerado',
        'fecha_recargo_anulado',
        'recargo_anulado_por',
        'observacion_recargo'
    ];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['updated']; // Solo auditar actualizaciones según requerimiento
    protected $granularAudit = false;

    // Relaciones
    public function rubro()
    {
        return $this->belongsTo(ConfigPlanPagoDetalle::class, 'rubro_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function arancel()
    {
        return $this->belongsTo(ConfigArancel::class, 'aranceles_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function recargoAnuladoPor()
    {
        return $this->belongsTo(User::class, 'recargo_anulado_por');
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

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopeExonerados($query)
    {
        return $query->where('estado', 'exonerado');
    }

    public function scopePorUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePorRubro($query, $rubroId)
    {
        return $query->where('rubro_id', $rubroId);
    }

    public function scopeConRecargo($query)
    {
        return $query->where('recargo', '>', 0);
    }

    public function scopeConSaldoPendiente($query)
    {
        return $query->where('saldo_actual', '>', 0);
    }

    // Métodos auxiliares
    public function tieneSaldoPendiente(): bool
    {
        return $this->saldo_actual > 0;
    }

    public function tieneRecargo(): bool
    {
        return $this->recargo > 0;
    }

    public function estaPagado(): bool
    {
        return $this->estado === 'pagado';
    }

    public function estaExonerado(): bool
    {
        return $this->estado === 'exonerado';
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'UsersAranceles',
            'user_id' => $this->user_id,
            'estado' => $this->estado,
            'saldo_actual' => $this->saldo_actual
        ];
    }
}
