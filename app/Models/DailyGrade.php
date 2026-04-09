<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyGrade extends Model
{
    protected $table = 'not_calificaciones_evidencias_diarias';

    protected $fillable = [
        'evidencia_diaria_id',
        'estudiante_id',
        'escala_detalle_id',
        'indicadores_check',
        'observacion'
    ];

    protected $casts = [
        'indicadores_check' => 'array',
    ];

    public function evidenciaDiaria()
    {
        return $this->belongsTo(DailyEvidence::class, 'evidencia_diaria_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function escalaDetalle()
    {
        return $this->belongsTo(ConfigNotEscalaDetalle::class, 'escala_detalle_id');
    }
}
