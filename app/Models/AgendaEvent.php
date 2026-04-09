<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgendaEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agenda_events';

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'color',
        'all_day',
        'event_url',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con los grupos a los que está dirigido el evento.
     * Si no tiene registros aquí, se asume que es para "Todos".
     */
    public function grupos()
    {
        return $this->belongsToMany(ConfigGrupo::class, 'agenda_event_grupo', 'agenda_event_id', 'grupo_id');
    }
}
