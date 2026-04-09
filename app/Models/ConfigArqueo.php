<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class ConfigArqueo extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'config_arqueo';

    protected $fillable = [
        'uuid','fecha','totalc','totald','tasacambio','totalarqueo',
        'created_by','updated_by','deleted_by','is_synced','synced_at','updated_locally_at','version'
    ];

    protected $casts = [
        'fecha' => 'date',
        'totalc' => 'decimal:2',
        'totald' => 'decimal:2',
        'tasacambio' => 'decimal:2',
        'totalarqueo' => 'decimal:2',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer'
    ];

    protected $dates = ['deleted_at','synced_at','updated_locally_at','fecha'];

    protected $auditableFields = ['fecha','totalc','totald','tasacambio','totalarqueo'];
    protected $nonAuditableFields = ['updated_at','created_at','deleted_at'];
    protected $auditableEvents = ['updated'];
    protected $granularAudit = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) { $model->uuid = Str::uuid(); }
        });
    }

    public function detalles()
    {
        return $this->hasMany(ConfigArqueoDetalle::class, 'arqueo_id');
    }

    public function getCustomAuditMetadata(): array
    {
        return ['model_name' => 'ConfigArqueo'];
    }
}

