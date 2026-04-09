<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Str;

class ConfigArqueoMoneda extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'config_arqueo_moneda';

    protected $fillable = [
        'uuid','moneda','denominacion','multiplicador','orden',
        'created_by','updated_by','deleted_by','is_synced','synced_at','updated_locally_at','version'
    ];

    protected $casts = [
        'moneda' => 'boolean',
        'multiplicador' => 'decimal:2',
        'orden' => 'integer',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
        'updated_locally_at' => 'datetime',
        'version' => 'integer'
    ];

    protected $dates = ['deleted_at','synced_at','updated_locally_at'];

    protected $auditableFields = ['moneda','denominacion','multiplicador','orden'];
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

    public function getCustomAuditMetadata(): array
    {
        return ['model_name' => 'ConfigArqueoMoneda'];
    }
}

