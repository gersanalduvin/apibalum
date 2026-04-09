<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvisoLectura extends Model
{
    protected $table = 'aviso_lecturas';

    protected $fillable = [
        'aviso_id',
        'user_id',
        'leido_at'
    ];

    protected $casts = [
        'leido_at' => 'datetime'
    ];

    public function aviso()
    {
        return $this->belongsTo(Aviso::class, 'aviso_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
