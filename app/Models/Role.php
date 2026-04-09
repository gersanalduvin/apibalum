<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\Auditable;

class Role extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'nombre',
        'permisos',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'permisos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = ['deleted_at'];

    // Configuración del trait Auditable
    protected $auditableFields = ['nombre', 'permisos'];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'
    ];
    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    // Boot method simplificado
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
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
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'role_name' => $this->nombre,
            'permissions_count' => is_array($this->permisos) ? count($this->permisos) : 0
    ];
    }

    /**
     * @deprecated Usar getRecentAudits() del trait Auditable
     */
    public function getHistorialCambios()
    {
        return $this->cambios ?? [];
    }

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
}
