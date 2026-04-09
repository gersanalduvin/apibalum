<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Producto extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'inventario_producto';

    protected $fillable = [
        'uuid',
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'unidad_medida',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'costo_promedio',
        'precio_venta',
        'moneda',
        'cuenta_inventario_id',
        'cuenta_costo_id',
        'cuenta_venta_id',
        'activo',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'uuid' => 'string',
        'stock_actual' => 'decimal:4',
        'stock_minimo' => 'decimal:4',
        'stock_maximo' => 'decimal:4',
        'costo_promedio' => 'decimal:4',
        'precio_venta' => 'float',
        'moneda' => 'boolean',
        'activo' => 'boolean',
        'maneja_inventario' => 'boolean',
        'permite_stock_negativo' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer'
    ];

    protected $dates = [
        'deleted_at',
        'synced_at',
        'updated_locally_at'
    ];

    // Configuración del trait Auditable
    protected $auditableFields = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'unidad_medida',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'costo_promedio',
        'precio_venta',
        'moneda',
        'cuenta_inventario_id',
        'cuenta_costo_id',
        'cuenta_venta_id',
        'activo'
    ];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    // Generar UUID automáticamente y manejar auditoría
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();

                // La auditoría se maneja en el servicio ProductoService
                // No registramos cambios aquí para evitar duplicados
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    // Relaciones con catálogo de cuentas
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function cuentaInventario()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_inventario_id');
    }

    public function cuentaCosto()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_costo_id');
    }

    public function cuentaVenta()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_venta_id');
    }

    // Relaciones de auditoría
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

    // Relaciones con inventario (para futuras tablas)
    public function movimientos()
    {
        return $this->hasMany(InventarioMovimiento::class, 'producto_id');
    }

    public function kardex()
    {
        return $this->hasMany(InventarioKardex::class, 'producto_id');
    }

    // Scopes útiles
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeManejaInventario($query)
    {
        return $query->where('maneja_inventario', true);
    }

    public function scopePorMoneda($query, $moneda)
    {
        return $query->where('moneda', $moneda);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo');
    }

    public function scopeNoSincronizados($query)
    {
        return $query->where('is_synced', false);
    }

    // Métodos auxiliares
    public function getMonedaTextoAttribute()
    {
        return $this->moneda ? 'Dólar' : 'Córdoba';
    }

    public function getStockDisponibleAttribute()
    {
        return $this->stock_actual;
    }

    public function tieneStockSuficiente($cantidad)
    {
        if (!$this->maneja_inventario) {
            return true;
        }

        if ($this->permite_stock_negativo) {
            return true;
        }

        return $this->stock_actual >= $cantidad;
    }

    public function estaEnStockMinimo()
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function actualizarCostoPromedio($nuevoCosto, $cantidad)
    {
        if ($this->stock_actual == 0) {
            $this->costo_promedio = $nuevoCosto;
        } else {
            $costoTotal = ($this->costo_promedio * $this->stock_actual) + ($nuevoCosto * $cantidad);
            $cantidadTotal = $this->stock_actual + $cantidad;
            $this->costo_promedio = $costoTotal / $cantidadTotal;
        }
    }

    // Registrar cambios para auditoría
    // Nota: El registro de cambios se maneja en ProductoService siguiendo el patrón estándar
    // Estructura: ['accion', 'usuario_email', 'fecha', 'datos_anteriores', 'datos_nuevos']

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'Producto'
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
