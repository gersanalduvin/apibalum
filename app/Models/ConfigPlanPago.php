<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Traits\Auditable;

class ConfigPlanPago extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'config_plan_pago';

    protected $fillable = [
        'uuid',
        'nombre',
        'estado',
        'periodo_lectivo_id',
        'created_by',
        'updated_by',
        'deleted_by',
'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'estado' => 'boolean',
'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
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
         'nombre',
        'estado',
        'periodo_lectivo_id'
    ];

    protected $nonAuditableFields = [
        'updated_at',
        'created_at',
        'deleted_at',
'is_synced',
        'synced_at',
        'updated_locally_at',
        'version',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    // Generar UUID automáticamente y manejar campos de auditoría básica
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
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();

                // Incrementar versión
                $model->version = ($model->version ?? 1) + 1;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });
    }

    /**
     * Metadatos personalizados para auditoría
     */
    protected function getCustomAuditMetadata(): array
    {
        return [
            'periodo_lectivo' => $this->periodoLectivo?->nombre,
            'total_detalles' => $this->detalles()->count()
    ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('estado', false);
    }

    public function scopeByPeriodoLectivo($query, $periodoLectivoId)
    {
        return $query->where('periodo_lectivo_id', $periodoLectivoId);
    }

    // Relaciones
    public function periodoLectivo()
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id');
    }

    public function detalles()
    {
        return $this->hasMany(ConfigPlanPagoDetalle::class, 'plan_pago_id');
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
    public function getEstadoTextAttribute()
    {
        return $this->estado ? 'Activo' : 'Inactivo';
    }

    public function getTotalDetallesAttribute()
    {
        return $this->detalles()->count();
    }

    public function getTotalImporteAttribute()
    {
        return $this->detalles()->sum('importe');
    }

    /**
     * Obtener historial de cambios (compatibilidad con sistema anterior)
     * @deprecated Usar $model->audits() en su lugar
     */
    public function getHistorialCambios()
    {
        return $this->getRecentAudits(50);
    }
}
