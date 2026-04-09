<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Auth;

class Recibo extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'recibos';

    protected $fillable = [
        'numero_recibo',
        'tipo',
        'user_id',
        'estado',
        'fecha',
        'nombre_usuario',
        'total',
        'grado',
        'seccion',
        'tasa_cambio',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
        'tasa_cambio' => 'decimal:4'
    ];

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
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(ReciboDetalle::class, 'recibo_id');
    }

    public function formasPago()
    {
        return $this->hasMany(ReciboFormaPago::class, 'recibo_id');
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