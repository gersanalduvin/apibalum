<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class ConfigArqueoDetalle extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'config_arqueo_detalle';

    protected $fillable = [
        'uuid','arqueo_id','moneda_id','cantidad','total',
        'created_by','updated_by','deleted_by','is_synced','synced_at','updated_locally_at','version'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'total' => 'decimal:2',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer'
    ];

    protected $dates = ['deleted_at','synced_at','updated_locally_at'];

    protected $auditableFields = ['arqueo_id','moneda_id','cantidad','total'];
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

    public function arqueo()
    {
        return $this->belongsTo(ConfigArqueo::class, 'arqueo_id');
    }

    public function moneda()
    {
        return $this->belongsTo(ConfigArqueoMoneda::class, 'moneda_id');
    }

    public function getCustomAuditMetadata(): array
    {
        return ['model_name' => 'ConfigArqueoDetalle'];
    }
}

