<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfPeriodoLectivo extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'conf_periodo_lectivos';

    protected $fillable = [
        'uuid',
        'nombre',
        'prefijo_alumno',
        'prefijo_docente',
        'prefijo_familia',
        'prefijo_admin',
        'incremento_alumno',
        'incremento_docente',
        'incremento_familia',
        'periodo_nota',
        'periodo_matricula',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'periodo_nota' => 'boolean',
        'periodo_matricula' => 'boolean',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = [
        'synced_at',
        'updated_locally_at',
        'deleted_at'
    ];

    // Configuración del trait Auditable
    protected $auditableFields = ['nombre',
        'prefijo_alumno',
        'prefijo_docente',
        'prefijo_familia',
        'prefijo_admin',
        'incremento_alumno',
        'incremento_docente',
        'incremento_familia',
        'periodo_nota',
        'periodo_matricula'];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'
    ]; // Mantener para compatibilidad temporal
    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    // Relaciones
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

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'ConfPeriodoLectivo'
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
