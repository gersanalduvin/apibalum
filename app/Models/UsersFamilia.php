<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsersFamilia extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'users_familia';

    protected $fillable = [
        'familia_id',
        'estudiante_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime'
    ];

    public function familia(): BelongsTo
    {
        return $this->belongsTo(User::class, 'familia_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }
}

