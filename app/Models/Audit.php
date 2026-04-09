<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Audit extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'model_type',
        'model_id',
        'event',
        'table_name',
        'column_name',
        'old_value',
        'new_value',
        'old_values',
        'new_values',
        'ip',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($audit) {
            if (empty($audit->uuid)) {
                $audit->uuid = Str::uuid();
            }
        });
    }

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación polimórfica con el modelo auditado
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Scope para filtrar por tipo de modelo
     */
    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    /**
     * Scope para filtrar por evento
     */
    public function scopeForEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Obtener el nombre del usuario que realizó la acción
     */
    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'Sistema';
    }

    /**
     * Obtener el email del usuario que realizó la acción
     */
    public function getUserEmailAttribute()
    {
        return $this->user ? $this->user->email : null;
    }

    /**
     * Formatear los cambios para mostrar
     */
    public function getFormattedChangesAttribute()
    {
        if ($this->column_name) {
            return [
                'campo' => $this->column_name,
                'valor_anterior' => $this->old_value,
                'valor_nuevo' => $this->new_value
    ];
        }

        if ($this->old_values && $this->new_values) {
            $changes = [];
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'anterior' => $oldValue,
                        'nuevo' => $newValue
    ];
                }
            }
            return $changes;
        }

        return null;
    }
}
