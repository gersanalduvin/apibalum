<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Auth;

class ReciboDetalle extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'recibos_detalle';

    protected $fillable = [
        'recibo_id',
        'rubro_id',
        'producto_id',
        'aranceles_id',
        'concepto',
        'cantidad',
        'monto',
        'descuento',
        'total',
        'tipo_pago',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'monto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2'
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

    public function recibo()
    {
        return $this->belongsTo(Recibo::class, 'recibo_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function rubro()
    {
        return $this->belongsTo(UsersAranceles::class, 'rubro_id');
    }

    public function arancel()
    {
        return $this->belongsTo(ConfigArancel::class, 'aranceles_id');
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
