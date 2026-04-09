<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class ConfigCatalogoCuentas extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'config_catalogo_cuentas';

    protected $fillable = [
        'uuid',
        'codigo',
        'nombre',
        'tipo',
        'nivel',
        'padre_id',
        'es_grupo',
        'permite_movimiento',
        'naturaleza',
        'descripcion',
        'estado',
        'moneda_usd',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'uuid' => 'string',
        'es_grupo' => 'boolean',
        'permite_movimiento' => 'boolean',
        'moneda_usd' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer',
        'nivel' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by'
    ];

     // Configuración del trait Auditable
    protected $auditableFields = ['codigo',
        'nombre',
        'tipo',
        'nivel',
        'padre_id',
        'es_grupo',
        'permite_movimiento',
        'naturaleza',
        'descripcion',
        'estado',
        'moneda_usd'];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
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
        });
    }

    // Relaciones
    public function padre()
    {
        return $this->belongsTo(ConfigCatalogoCuentas::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(ConfigCatalogoCuentas::class, 'padre_id');
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
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeInactivos($query)
    {
        return $query->where('estado', 'inactivo');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeGrupos($query)
    {
        return $query->where('es_grupo', true);
    }

    public function scopePermiteMovimiento($query)
    {
        return $query->where('permite_movimiento', true);
    }

    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopePorNaturaleza($query, $naturaleza)
    {
        return $query->where('naturaleza', $naturaleza);
    }

    public function scopeMonedaUsd($query)
    {
        return $query->where('moneda_usd', true);
    }

    public function scopeMonedaCordobas($query)
    {
        return $query->where('moneda_usd', false);
    }

    public function scopeNoSincronizados($query)
    {
        return $query->where('is_synced', false);
    }

    public function scopeActualizadosDespues($query, $fecha)
    {
        return $query->where('updated_at', '>', $fecha);
    }

    // Métodos auxiliares
    public function getCodigoCompletoAttribute()
    {
        $codigos = collect([$this->codigo]);
        $padre = $this->padre;

        while ($padre) {
            $codigos->prepend($padre->codigo);
            $padre = $padre->padre;
        }

        return $codigos->implode('.');
    }

    public function getNombreCompletoAttribute()
    {
        $nombres = collect([$this->nombre]);
        $padre = $this->padre;

        while ($padre) {
            $nombres->prepend($padre->nombre);
            $padre = $padre->padre;
        }

        return $nombres->implode(' > ');
    }

    public function esHoja()
    {
        return $this->hijos()->count() === 0;
    }

    public function tieneHijos()
    {
        return $this->hijos()->count() > 0;
    }

    public function getDescendientes()
    {
        $descendientes = collect();

        foreach ($this->hijos as $hijo) {
            $descendientes->push($hijo);
            $descendientes = $descendientes->merge($hijo->getDescendientes());
        }

        return $descendientes;
    }

    public function getAscendientes()
    {
        $ascendientes = collect();
        $padre = $this->padre;

        while ($padre) {
            $ascendientes->push($padre);
            $padre = $padre->padre;
        }

        return $ascendientes;
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
            'model_name' => 'ConfigCatalogoCuentas'
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
