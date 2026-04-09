<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConfigPlanPagoDetalle extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'config_plan_pago_detalle';

    protected $fillable = [
        'uuid',
        'plan_pago_id',
        'codigo',
        'nombre',
        'importe',
        'cuenta_debito_id',
        'cuenta_credito_id',
        'cuenta_recargo_id',
        'es_colegiatura',
        'asociar_mes',
        'orden_mes',
        'fecha_vencimiento',
        'importe_recargo',
        'tipo_recargo',
        'moneda',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'importe' => 'decimal:2',
        'importe_recargo' => 'decimal:2',
        'es_colegiatura' => 'boolean',
        'moneda' => 'boolean',
        'orden_mes' => 'integer',
        'fecha_vencimiento' => 'date',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'orden_mes' => 0
    ];

    protected $hidden = [
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

        /**
         * Configuración de auditoría
         */
    protected $auditableFields = [
         'plan_pago_id',
        'codigo',
        'nombre',
        'importe',
        'cuenta_debito_id',
        'cuenta_credito_id',
        'cuenta_recargo_id',
        'es_colegiatura',
        'asociar_mes',
        'orden_mes',
        'fecha_vencimiento',
        'importe_recargo',
        'tipo_recargo',
        'moneda'
    ];

    protected $nonAuditableFields = [
        'updated_at',
        'created_at',
        'deleted_at',
    ];

    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    // Generar UUID automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }

            // Actualizar orden_mes automáticamente al crear
            $model->updateOrdenMes();
        });

        static::updating(function ($model) {
            // Actualizar orden_mes automáticamente al editar si asociar_mes cambió
            if ($model->isDirty('asociar_mes')) {
                $model->updateOrdenMes();
            }
        });
    }

    /**
     * Actualiza el campo orden_mes basado en el valor de asociar_mes
     */
    protected function updateOrdenMes()
    {
        $mesesOrden = [
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12
    ];

        $mesNormalizado = strtolower(trim($this->asociar_mes ?? ''));
        $this->orden_mes = $mesesOrden[$mesNormalizado] ?? 0;
    }

    // Mutator para asociar_mes que actualiza orden_mes automáticamente
    public function setAsociarMesAttribute($value)
    {
        // Validar que el mes sea válido antes de asignarlo
        $mesesValidos = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];

        $mesNormalizado = strtolower(trim($value ?? ''));

        // Solo asignar si es un mes válido o null/vacío
        if (empty($value) || in_array($mesNormalizado, $mesesValidos)) {
            $this->attributes['asociar_mes'] = $value;

            // Actualizar orden_mes cuando se establece asociar_mes
            $mesesOrden = [
                'enero' => 1,
                'febrero' => 2,
                'marzo' => 3,
                'abril' => 4,
                'mayo' => 5,
                'junio' => 6,
                'julio' => 7,
                'agosto' => 8,
                'septiembre' => 9,
                'octubre' => 10,
                'noviembre' => 11,
                'diciembre' => 12
    ];

            $this->attributes['orden_mes'] = $mesesOrden[$mesNormalizado] ?? 0;
        } else {
            // Si el mes no es válido, mantener el valor anterior y establecer orden_mes en 0
            $this->attributes['orden_mes'] = 0;
        }
    }

    // Scopes
    public function scopeByColegiatura($query, $esColegiatura = true)
    {
        return $query->where('es_colegiatura', $esColegiatura);
    }

    public function scopeByMes($query, $mes)
    {
        return $query->where('asociar_mes', $mes);
    }

    public function scopeByMoneda($query, $moneda)
    {
        return $query->where('moneda', $moneda);
    }

    public function scopeByPlanPago($query, $planPagoId)
    {
        return $query->where('plan_pago_id', $planPagoId);
    }

    // Relaciones
    public function planPago()
    {
        return $this->belongsTo(ConfigPlanPago::class, 'plan_pago_id');
    }

    public function cuentaDebito()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_debito_id');
    }

    public function cuentaCredito()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_credito_id');
    }

    public function cuentaRecargo()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_recargo_id');
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

    // Accessors
    public function getMonedaTextAttribute()
    {
        return $this->moneda ? 'Dólar' : 'Córdoba';
    }

    public function getEsColegiaturaTextAttribute()
    {
        return $this->es_colegiatura ? 'Sí' : 'No';
    }

    public function getTipoRecargoTextAttribute()
    {
        return $this->tipo_recargo ? ucfirst($this->tipo_recargo) : 'N/A';
    }

    public function getImporteFormateadoAttribute()
    {
        $simbolo = $this->moneda ? 'US$' : 'C$';
        return $simbolo . ' ' . number_format($this->importe, 2);
    }

    public function getImporteRecargoFormateadoAttribute()
    {
        if (!$this->importe_recargo) {
            return 'N/A';
        }

        $simbolo = $this->moneda ? 'US$' : 'C$';
        $valor = number_format($this->importe_recargo, 2);

        if ($this->tipo_recargo === 'porcentaje') {
            return $valor . '%';
        }

        return $simbolo . ' ' . $valor;
    }

    public function getAsociarMesTextAttribute()
    {
        return $this->asociar_mes ? ucfirst($this->asociar_mes) : 'N/A';
    }

    /**
     * Obtiene todos los campos que deben incluirse en el registro de cambios
     * Excluye relaciones, campos de auditoría y el propio campo cambios
     */
    public function getCamposParaCambios(): array
    {
        // Campos que NO deben incluirse en el registro de cambios
        $camposExcluidos = [
            // Campo de cambios
            // Campos de auditoría automáticos
            'created_by',
            'updated_by',
            'deleted_by',
            'created_at',
            'updated_at',
            'deleted_at',
            // Campos de sincronización
            'is_synced',
            'synced_at',
            'updated_locally_at',
            'version',
            // ID primario
            'id'
        ];

        // Obtener todos los campos fillable y filtrar los excluidos
        $camposParaCambios = array_diff($this->fillable, $camposExcluidos);

        return array_values($camposParaCambios);
    }

    /**
     * Obtiene los datos actuales de los campos que deben registrarse en cambios
     */
    public function getDatosParaCambios(): array
    {
        $campos = $this->getCamposParaCambios();
        $datos = [];

        foreach ($campos as $campo) {
            $datos[$campo] = $this->getAttribute($campo);
        }

        return $datos;
    }

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'ConfigPlanPagoDetalle'
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
