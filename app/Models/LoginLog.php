<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    use HasFactory;

    protected $table = 'login_logs';

    /**
     * Disable the updated_at timestamp as we only need to know when the login occurred.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the user that owns the login log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
