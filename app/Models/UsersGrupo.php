<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsersGrupo extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'users_grupos';

    protected $fillable = [
        'user_id',
        'fecha_matricula',
        'periodo_lectivo_id',
        'grado_id',
        'grupo_id',
        'turno_id',
        'numero_recibo',
        'tipo_ingreso',
        'estado',
        'activar_estadistica',
        'corte_retiro',
        'corte_ingreso',
        'maestra_anterior',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'fecha_matricula' => 'date',
        'activar_estadistica' => 'boolean',
        'deleted_at' => 'datetime'
    ];

    // Constantes para los valores del enum tipo_ingreso
    const TIPO_INGRESO_REINGRESO = 'reingreso';
    const TIPO_INGRESO_NUEVO = 'nuevo_ingreso';
    const TIPO_INGRESO_TRASLADO = 'traslado';

    // Array con los valores válidos del enum
    public static function getTiposIngreso(): array
    {
        return [
            self::TIPO_INGRESO_REINGRESO,
            self::TIPO_INGRESO_NUEVO,
            self::TIPO_INGRESO_TRASLADO
        ];
    }

    protected $dates = [
        'fecha_matricula',
        'deleted_at'
    ];

    // Configuración del trait Auditable
    protected $auditableFields = [
        'fecha_matricula',
        'periodo_lectivo_id',
        'grado_id',
        'grupo_id',
        'turno_id',
        'numero_recibo',
        'tipo_ingreso',
        'estado',
        'activar_estadistica',
        'corte_retiro',
        'corte_ingreso',
        'maestra_anterior'
    ];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['created', 'updated', 'deleted'];
    protected $granularAudit = false;

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periodoLectivo(): BelongsTo
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id')->withTrashed();
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(ConfigGrado::class, 'grado_id')->withTrashed();
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(ConfigGrupo::class, 'grupo_id')->withTrashed();
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(ConfigTurnos::class, 'turno_id')->withTrashed();
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeConEstadistica($query)
    {
        return $query->where('activar_estadistica', true);
    }

    public function scopePorPeriodo($query, $periodoId)
    {
        return $query->where('periodo_lectivo_id', $periodoId);
    }

    // Mutators
    public function setTipoIngresoAttribute($value)
    {
        // Validar que el valor esté en los valores permitidos del enum
        if (!in_array($value, self::getTiposIngreso())) {
            throw new \InvalidArgumentException("Tipo de ingreso inválido: {$value}");
        }
        $this->attributes['tipo_ingreso'] = $value;
    }

    // Accessors
    public function getTipoIngresoAttribute($value)
    {
        return $value;
    }

    /**
     * Accessor para mostrar el tipo de ingreso en formato legible
     */
    public function getTipoIngresoTextoAttribute(): string
    {
        $textos = [
            self::TIPO_INGRESO_REINGRESO => 'Reingreso',
            self::TIPO_INGRESO_NUEVO => 'Nuevo Ingreso',
            self::TIPO_INGRESO_TRASLADO => 'Traslado'
        ];

        return $textos[$this->tipo_ingreso] ?? 'No definido';
    }

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'UsersGrupo'
        ];
    }

    /**
     * @deprecated Usar getRecentAudits() del trait Auditable
     */
    public function getHistorialCambios()
    {
        return $this->cambios ?? [];
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
}
