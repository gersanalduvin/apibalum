<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class ConfigGrado extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'config_grado';

    protected $fillable = [
        'uuid',
        'nombre',
        'formato',
        'abreviatura',
        'orden',
        'modalidad_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_synced',
        'synced_at',
        'updated_locally_at',
        'version'
    ];

    protected $casts = [
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer',
        'orden' => 'integer',
        'modalidad_id' => 'integer'
    ];

    protected $dates = [
        'deleted_at',
        'synced_at',
        'updated_locally_at'
    ];

    // Configuración del trait Auditable
    protected $auditableFields = [ 'nombre','abreviatura','orden','modalidad_id'];
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
        });
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

    public function grupos()
    {
        return $this->hasMany(ConfigGrupos::class, 'grado_id');
    }

    public function modalidad()
    {
        return $this->belongsTo(ConfigModalidad::class, 'modalidad_id');
    }

    /**
     * Obtener metadata personalizada para auditoría
     */
    public function getCustomAuditMetadata(): array
    {
        return [
            'model_name' => 'ConfigGrado'
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
