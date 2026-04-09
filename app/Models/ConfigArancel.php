<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class ConfigArancel extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'config_aranceles';

    protected $fillable = [
        'uuid',
        'codigo',
        'nombre',
        'precio',
        'moneda',
        'activo',
        'cuenta_debito_id',
        'cuenta_credito_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'moneda' => 'boolean',
        'activo' => 'boolean',
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
        'precio',
        'moneda',
        'activo',
        'cuenta_debito_id',
        'cuenta_credito_id'
    ];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            $model->version = 1;
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
            $model->version++;
            $model->updated_locally_at = now();
            // La auditoría se maneja automáticamente por el trait Auditable
        });

        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    // Relaciones
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'config_aranceles_productos', 'config_arancel_id', 'producto_id')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function cuentaDebito()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_debito_id');
    }

    public function cuentaCredito()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'cuenta_credito_id');
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
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByCodigo($query, $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    public function scopeByMoneda($query, $moneda)
    {
        return $query->where('moneda', $moneda);
    }

    public function scopeNotSynced($query)
    {
        return $query->where('is_synced', false);
    }

    public function scopeUpdatedAfter($query, $date)
    {
        return $query->where('updated_at', '>', $date);
    }

    // Mutators
    public function setCodigoAttribute($value)
    {
        $this->attributes['codigo'] = strtoupper($value);
    }

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = ucwords(strtolower($value));
    }

    // Accessors
    public function getMonedaTextAttribute()
    {
        return $this->moneda ? 'Dólar' : 'Córdoba';
    }

    public function getPrecioFormateadoAttribute()
    {
        $simbolo = $this->moneda ? '$' : 'C$';
        return $simbolo . ' ' . number_format($this->precio, 2);
    }

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'ConfigArancel'
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
