<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Models\User;
use App\Models\ConfigGrupo;
use App\Models\ConfPeriodoLectivo;
use App\Models\ConfigNotSemestreParcial;
use App\Models\NotAsignaturaGrado;
use Illuminate\Support\Facades\Storage;

class LessonPlan extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lesson_plans';

    protected $fillable = [
        'user_id',
        'periodo_lectivo_id',
        'parcial_id',
        'asignatura_id',
        'is_general',
        'nivel',
        'start_date',
        'end_date',
        'contenido',
        'archivo_url',
        'is_submitted',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_submitted' => 'boolean',
        'is_general' => 'boolean',
        'contenido' => 'array',
    ];

    protected $appends = ['file_full_url'];

    // Accessors
    public function getFileFullUrlAttribute()
    {
        return $this->archivo_url ? Storage::disk('s3')->url($this->archivo_url) : null;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function periodoLectivo()
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id');
    }

    public function parcial()
    {
        return $this->belongsTo(ConfigNotSemestreParcial::class, 'parcial_id');
    }

    // Assuming 'asignatura_id' refers to NotAsignaturaGrado or a similar model based on context.
    // Since the instruction was "asignatura_id, será la asignatura asignada al docente", 
    // it likely links to the specific subject record.
    // For now we might not need an explicit relationship method if we just use the ID, 
    // but defining it helps. I'll link to NotAsignaturaGrado for now, as that is common.
    public function asignatura()
    {
        return $this->belongsTo(NotAsignaturaGrado::class, 'asignatura_id');
    }

    public function groups()
    {
        return $this->belongsToMany(ConfigGrupo::class, 'lesson_plan_groups', 'lesson_plan_id', 'grupo_id');
    }

    // Audit relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
