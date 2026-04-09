<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Auth;

class ReciboFormaPago extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'recibos_forma_pago';

    protected $fillable = [
        'recibo_id',
        'forma_pago_id',
        'monto',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'monto' => 'decimal:2'
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

    public function formaPago()
    {
        return $this->belongsTo(ConfigFormaPago::class, 'forma_pago_id');
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