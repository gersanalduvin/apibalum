<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class InventarioMovimiento extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'inventario_movimientos';

    protected $fillable = [
        'uuid',
        'producto_id',
        'tipo_movimiento',
        'subtipo_movimiento',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'stock_anterior',
        'stock_posterior',
        'costo_promedio_anterior',
        'costo_promedio_posterior',
        'precio_venta',
        'moneda',
        'documento_tipo',
        'documento_numero',
        'documento_fecha',
        'proveedor_id',
        'cliente_id',
        'observaciones',
        'ubicacion',
        'lote',
        'fecha_vencimiento',
        'activo',
        'reversible',
        'movimiento_reverso_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'costo_unitario' => 'decimal:4',
        'costo_total' => 'decimal:4',
        'stock_anterior' => 'decimal:4',
        'stock_posterior' => 'decimal:4',
        'costo_promedio_anterior' => 'decimal:4',
        'costo_promedio_posterior' => 'decimal:4',
        'moneda' => 'boolean',
        'documento_fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'activo' => 'boolean',
        'reversible' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer'
    ];

    protected $dates = [
        'documento_fecha',
        'fecha_vencimiento',
        'synced_at',
        'updated_locally_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];



    // Generar UUID automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            // Auditoría automática
            $user = auth()->user();
            if ($user) {
                $model->created_by = $user->id;
                $model->updated_by = $user->id;
            }
            // La auditoría se maneja automáticamente por el trait Auditable
        });

        static::updating(function ($model) {
            // Auditoría automática
            $user = auth()->user();
            if ($user) {
                $model->updated_by = $user->id;
            }
            // La auditoría se maneja automáticamente por el trait Auditable
        });

        static::deleting(function ($model) {
            // Auditoría automática
            $user = auth()->user();
            if ($user) {
                $model->deleted_by = $user->id;
                $model->save();
            }
            // La auditoría se maneja automáticamente por el trait Auditable
        });
    }

    // === RELACIONES ===

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Relación con el proveedor (usuario)
     */
    public function proveedor()
    {
        return $this->belongsTo(User::class, 'proveedor_id');
    }

    /**
     * Relación con el cliente (usuario)
     */
    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    /**
     * Usuario que realizó el movimiento (usando created_by)
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que creó el registro
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que actualizó el registro
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Usuario que eliminó el registro
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Movimiento que reversa este movimiento
     */
    public function movimientoReverso()
    {
        return $this->belongsTo(InventarioMovimiento::class, 'movimiento_reverso_id');
    }

    /**
     * Movimientos que son reversados por este
     */
    public function movimientosReversados()
    {
        return $this->hasMany(InventarioMovimiento::class, 'movimiento_reverso_id');
    }

    /**
     * Registro en el kardex
     */
    public function kardex()
    {
        return $this->hasOne(InventarioKardex::class, 'movimiento_id');
    }

    // === SCOPES ===

    /**
     * Filtrar movimientos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Filtrar por tipo de movimiento
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    /**
     * Filtrar por subtipo de movimiento
     */
    public function scopePorSubtipo($query, $subtipo)
    {
        return $query->where('subtipo_movimiento', $subtipo);
    }

    /**
     * Filtrar por producto
     */
    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    /**
     * Filtrar por moneda
     */
    public function scopePorMoneda($query, $moneda)
    {
        return $query->where('moneda', $moneda);
    }

    /**
     * Filtrar por rango de fechas
     */
    public function scopePorFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
    }

    /**
     * Filtrar movimientos no sincronizados
     */
    public function scopeNoSincronizados($query)
    {
        return $query->where('is_synced', false);
    }

    /**
     * Filtrar movimientos reversibles
     */
    public function scopeReversibles($query)
    {
        return $query->where('reversible', true)->whereNull('movimiento_reverso_id');
    }

    // === MÉTODOS AUXILIARES ===

    /**
     * Obtener el texto de la moneda
     */
    public function getMonedaTextoAttribute()
    {
        return $this->moneda ? 'Dólar' : 'Córdoba';
    }

    /**
     * Verificar si es un movimiento de entrada
     */
    public function esEntrada()
    {
        return in_array($this->tipo_movimiento, ['entrada', 'ajuste_positivo']);
    }

    /**
     * Verificar si es un movimiento de salida
     */
    public function esSalida()
    {
        return in_array($this->tipo_movimiento, ['salida', 'ajuste_negativo']);
    }

    /**
     * Verificar si el movimiento puede ser reversado
     */
    public function puedeSerReversado()
    {
        return $this->reversible &&
            $this->activo &&
            is_null($this->movimiento_reverso_id) &&
            is_null($this->deleted_at);
    }

    /**
     * Calcular el costo total del movimiento
     */
    public function calcularCostoTotal()
    {
        return $this->cantidad * $this->costo_unitario;
    }

    /**
     * Registrar cambio (DEPRECATED - usar trait Auditable)
     * @deprecated Este método es legacy, la auditoría se maneja automáticamente
     */
    public function registrarCambio($campo, $valorAnterior, $valorNuevo, $usuario = null)
    {
        // Este método es legacy - la auditoría se maneja automáticamente por el trait Auditable
        // Se mantiene por compatibilidad pero no realiza ninguna acción
        return;
    }

    /**
     * Crear movimiento de reverso
     */
    public function crearMovimientoReverso($observaciones = null, $usuario = null)
    {
        if (!$this->puedeSerReversado()) {
            throw new \Exception('Este movimiento no puede ser reversado');
        }

        $movimientoReverso = self::create([
            'producto_id' => $this->producto_id,
            'tipo_movimiento' => $this->esEntrada() ? 'salida' : 'entrada',
            'subtipo_movimiento' => 'correccion_sistema',
            'cantidad' => $this->cantidad,
            'costo_unitario' => $this->costo_unitario,
            'costo_total' => $this->costo_total,
            'moneda' => $this->moneda,
            'documento_tipo' => 'REVERSO',
            'documento_numero' => 'REV-' . $this->id,
            'documento_fecha' => now()->toDateString(),
            'observaciones' => $observaciones ?? "Reverso del movimiento #{$this->id}",
            'movimiento_reverso_id' => null,
            'reversible' => false,
            'created_by' => $usuario?->id ?? auth()->id()
        ]);

        // Marcar este movimiento como reversado
        $this->update(['movimiento_reverso_id' => $movimientoReverso->id]);

        return $movimientoReverso;
    }

    /**
     * Obtener el período contable del movimiento
     */
    public function getPeriodoContable()
    {
        $fecha = $this->documento_fecha ?? $this->created_at ?? now();
        return [
            'year' => $fecha->year,
            'month' => $fecha->month,
            'fecha' => $fecha->toDateString()
        ];
    }

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'InventarioMovimiento'
        ];
    }

    /**
     * @deprecated Usar getRecentAudits() del trait Auditable
     */
    public function getHistorialCambios()
    {
        return $this->cambios ?? [];
    }
}
