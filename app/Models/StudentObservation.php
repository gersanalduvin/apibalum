<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class StudentObservation extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'user_id',
        'periodo_lectivo_id',
        'parcial_id',
        'grupo_id',
        'observacion',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'periodo_lectivo_id' => 'integer',
        'parcial_id' => 'integer',
        'grupo_id' => 'integer'
    ];

    // Relaciones
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function periodoLectivo()
    {
        return $this->belongsTo(ConfPeriodoLectivo::class, 'periodo_lectivo_id');
    }

    public function parcial()
    {
        return $this->belongsTo(ConfigNotSemestreParcial::class, 'parcial_id');
    }

    public function grupo()
    {
        return $this->belongsTo(ConfigGrupo::class, 'grupo_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Configuración Auditable
    protected $auditableFields = ['observacion'];
    protected $nonAuditableFields = ['updated_at', 'created_at', 'deleted_at'];
    protected $auditableEvents = ['updated'];
}
