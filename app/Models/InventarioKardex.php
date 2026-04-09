<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class InventarioKardex extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'inventario_kardex';

    protected $fillable = [
        'uuid',
        'producto_id',
        'movimiento_id',
        'tipo_movimiento',
        'cantidad',
        'costo_unitario',
        'stock_anterior',
        'valor_anterior',
        'costo_promedio_anterior',
        'valor_movimiento',
        'stock_posterior',
        'valor_posterior',
        'valor_posterior',
        'costo_promedio_posterior',
        'precio_venta',
        'moneda',
        'documento_tipo',
        'documento_numero',
        'periodo_year',
        'periodo_month',
        'fecha_movimiento',
        'activo',
        'es_ajuste_inicial',
        'es_cierre_periodo',
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
        'stock_anterior' => 'decimal:4',
        'valor_anterior' => 'decimal:4',
        'costo_promedio_anterior' => 'decimal:4',
        'valor_movimiento' => 'decimal:4',
        'stock_posterior' => 'decimal:4',
        'valor_posterior' => 'decimal:4',
        'costo_promedio_posterior' => 'decimal:4',
        'precio_venta' => 'decimal:2',
        'moneda' => 'boolean',
        'documento_fecha' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_movimiento' => 'date',
        'activo' => 'boolean',
        'es_ajuste_inicial' => 'boolean',
        'es_cierre_periodo' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer'
    ];

    protected $dates = [
        'documento_fecha',
        'fecha_vencimiento',
        'fecha_movimiento',
        'synced_at',
        'updated_locally_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];


    // Generar UUID automáticamente y manejar auditoría
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }

            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
            // La auditoría se maneja automáticamente por el trait Auditable
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
            // La auditoría se maneja automáticamente por el trait Auditable
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
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
     * Relación con el movimiento de inventario
     */
    public function movimiento()
    {
        return $this->belongsTo(InventarioMovimiento::class, 'movimiento_id');
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

    // === SCOPES ===

    /**
     * Filtrar registros activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Filtrar por producto
     */
    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    /**
     * Filtrar por tipo de movimiento
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    /**
     * Filtrar por moneda
     */
    public function scopePorMoneda($query, $moneda)
    {
        return $query->where('moneda', $moneda);
    }

    /**
     * Filtrar por período
     */
    public function scopePorPeriodo($query, $year, $month = null)
    {
        $query->where('periodo_year', $year);
        if ($month) {
            $query->where('periodo_month', $month);
        }
        return $query;
    }

    /**
     * Filtrar por rango de fechas
     */
    public function scopePorFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin]);
    }

    /**
     * Filtrar ajustes iniciales
     */
    public function scopeAjustesIniciales($query)
    {
        return $query->where('es_ajuste_inicial', true);
    }

    /**
     * Filtrar cierres de período
     */
    public function scopeCierresPeriodo($query)
    {
        return $query->where('es_cierre_periodo', true);
    }

    /**
     * Ordenar por fecha de movimiento
     */
    public function scopeOrdenadoPorFecha($query, $direccion = 'asc')
    {
        return $query->orderBy('fecha_movimiento', $direccion)->orderBy('id', $direccion);
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
     * Calcular el costo promedio después del movimiento
     */
    public static function calcularCostoPromedio($stockAnterior, $valorAnterior, $cantidadMovimiento, $costoUnitarioMovimiento, $esEntrada)
    {
        if ($esEntrada) {
            // Para entradas: (Valor anterior + Valor del movimiento) / (Stock anterior + Cantidad del movimiento)
            $stockNuevo = $stockAnterior + $cantidadMovimiento;
            $valorNuevo = $valorAnterior + ($cantidadMovimiento * $costoUnitarioMovimiento);

            return $stockNuevo > 0 ? $valorNuevo / $stockNuevo : 0;
        } else {
            // Para salidas: el costo promedio se mantiene igual
            return $stockAnterior > 0 ? $valorAnterior / $stockAnterior : 0;
        }
    }

    /**
     * Crear registro de kardex desde un movimiento
     */
    public static function crearDesdeMovimiento(InventarioMovimiento $movimiento)
    {
        // Obtener el último registro del kardex para este producto y moneda
        $ultimoKardex = self::where('producto_id', $movimiento->producto_id)
            ->where('moneda', $movimiento->moneda)
            ->where('activo', true)
            ->orderBy('fecha_movimiento', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        // Valores anteriores
        $stockAnterior = $ultimoKardex ? $ultimoKardex->stock_posterior : ($movimiento->stock_anterior ?? 0);
        $valorAnterior = $ultimoKardex ? $ultimoKardex->valor_posterior : ($stockAnterior * ($movimiento->costo_promedio_anterior ?? 0));
        $costoPromedioAnterior = $ultimoKardex ? $ultimoKardex->costo_promedio_posterior : ($movimiento->costo_promedio_anterior ?? 0);

        // Calcular valores del movimiento
        $esEntrada = in_array($movimiento->tipo_movimiento, ['entrada', 'ajuste_positivo']);
        $cantidadMovimiento = $movimiento->cantidad;
        $valorMovimiento = $cantidadMovimiento * $movimiento->costo_unitario;

        // Calcular valores posteriores
        if ($esEntrada) {
            $stockPosterior = $stockAnterior + $cantidadMovimiento;
            $valorPosterior = $valorAnterior + $valorMovimiento;
        } else {
            $stockPosterior = $stockAnterior - $cantidadMovimiento;
            $valorPosterior = $valorAnterior - ($cantidadMovimiento * $costoPromedioAnterior);
        }

        // Calcular nuevo costo promedio
        $costoPromedioPosterior = self::calcularCostoPromedio(
            $stockAnterior,
            $valorAnterior,
            $cantidadMovimiento,
            $movimiento->costo_unitario,
            $esEntrada
        );

        // Ensure not null
        $costoPromedioPosterior = $costoPromedioPosterior ?? 0;

        // Obtener período contable
        $periodo = $movimiento->getPeriodoContable();

        // Crear registro de kardex
        return self::create([
            'producto_id' => $movimiento->producto_id,
            'movimiento_id' => $movimiento->id,
            'tipo_movimiento' => $movimiento->tipo_movimiento,
            'cantidad' => $cantidadMovimiento,
            'costo_unitario' => $movimiento->costo_unitario,
            'stock_anterior' => $stockAnterior,
            'valor_anterior' => $valorAnterior,
            'costo_promedio_anterior' => $costoPromedioAnterior,
            'valor_movimiento' => $valorMovimiento,
            'stock_posterior' => $stockPosterior,
            'valor_posterior' => $valorPosterior,
            'costo_promedio_posterior' => $costoPromedioPosterior,
            'moneda' => $movimiento->moneda,
            'documento_tipo' => $movimiento->documento_tipo,
            'documento_numero' => $movimiento->documento_numero,
            'periodo_year' => $periodo['year'],
            'periodo_month' => $periodo['month'],
            'fecha_movimiento' => $periodo['fecha'],
            'created_by' => $movimiento->created_by
        ]);
    }

    /**
     * Obtener el kardex de un producto en un rango de fechas
     */
    public static function obtenerKardexProducto($productoId, $fechaInicio, $fechaFin, $moneda = null)
    {
        $query = self::where('producto_id', $productoId)
            ->where('activo', true)
            ->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin])
            ->ordenadoPorFecha();

        if (!is_null($moneda)) {
            $query->where('moneda', $moneda);
        }

        return $query->with(['movimiento', 'producto'])->get();
    }

    /**
     * Obtener el stock actual de un producto
     */
    public static function obtenerStockActual($productoId, $moneda = false)
    {
        $ultimoKardex = self::where('producto_id', $productoId)
            ->where('moneda', $moneda)
            ->where('activo', true)
            ->orderBy('fecha_movimiento', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $ultimoKardex ? $ultimoKardex->stock_posterior : 0;
    }

    /**
     * Obtener el costo promedio actual de un producto
     */
    public static function obtenerCostoPromedioActual($productoId, $moneda = false)
    {
        $ultimoKardex = self::where('producto_id', $productoId)
            ->where('moneda', $moneda)
            ->where('activo', true)
            ->orderBy('fecha_movimiento', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $ultimoKardex ? $ultimoKardex->costo_promedio_posterior : 0;
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
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'InventarioKardex'
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
