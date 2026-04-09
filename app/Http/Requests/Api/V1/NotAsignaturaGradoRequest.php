<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class NotAsignaturaGradoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $periodoId = (int) $this->input('periodo_lectivo_id');
        $gradoId = (int) $this->input('grado_id');
        $currentId = $this->route('id') ? (int) $this->route('id') : (int) $this->input('id');

        $corteRules = [
            'cortes' => 'required|array|min:1',
            'cortes.*.id' => 'sometimes|integer|exists:not_asignatura_grado_cortes,id',
            'cortes.*.corte_id' => 'required|integer|exists:config_not_semestre_parciales,id',
            'cortes.*.evidencias' => 'nullable|array',
            'cortes.*.evidencias.*.id' => 'sometimes|integer|exists:not_asignatura_grado_cortes_evidencias,id',
            'cortes.*.evidencias.*.evidencia' => 'required|string',
            'cortes.*.evidencias.*.indicador' => 'nullable',
        ];

        $paramRules = [
            'parametros' => 'required|array|min:1',
            'parametros.*.id' => 'sometimes|integer|exists:not_asignatura_parametros,id',
            'parametros.*.parametro' => 'required|string|max:250',
            'parametros.*.valor' => 'required|string|max:180',
        ];

        $hijasRules = [
            'hijas' => 'nullable|array',
            'hijas.*.asignatura_hija_id' => 'nullable|integer|exists:not_asignatura_grado,id',
        ];

        $baseRules = [
            'id' => 'sometimes|integer|exists:not_asignatura_grado,id',
            'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
            'grado_id' => 'required|integer|exists:config_grado,id',
            'materia_id' => [
                'required',
                'integer',
                'exists:not_materias,id',
                Rule::unique('not_asignatura_grado', 'materia_id')
                    ->where(function ($q) use ($periodoId, $gradoId) {
                        return $q->where('periodo_lectivo_id', $periodoId)
                            ->where('grado_id', $gradoId)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($currentId)
            ],
            'escala_id' => 'required|integer|exists:config_not_escala,id',
            'nota_aprobar' => 'required|integer|min:0|max:999',
            'nota_maxima' => 'required|integer|min:0|max:999',
            'incluir_en_promedio' => 'required|boolean',
            'incluir_en_reporte_mined' => 'required|boolean',
            'incluir_horario' => 'required|boolean',
            'incluir_boletin' => 'required|boolean',
            'mostrar_escala' => 'required|boolean',
            'orden' => 'sometimes|integer|min:0|max:999',
            'tipo_evaluacion' => 'required|in:promedio,sumativa',
            'es_para_educacion_iniciativa' => 'required|boolean',
            'permitir_copia' => 'required|boolean',
            'incluir_plan_clase' => 'required|boolean',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $baseRules['periodo_lectivo_id'] = 'sometimes|required|integer|exists:conf_periodo_lectivos,id';
            $baseRules['grado_id'] = 'sometimes|required|integer|exists:config_grado,id';
            $baseRules['materia_id'] = [
                'sometimes',
                'required',
                'integer',
                'exists:not_materias,id',
                Rule::unique('not_asignatura_grado', 'materia_id')
                    ->where(function ($q) use ($periodoId, $gradoId) {
                        return $q->where('periodo_lectivo_id', $periodoId)
                            ->where('grado_id', $gradoId)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($currentId)
            ];
            $baseRules['escala_id'] = 'sometimes|required|integer|exists:config_not_escala,id';
            $baseRules['nota_aprobar'] = 'sometimes|required|integer|min:0|max:999';
            $baseRules['nota_maxima'] = 'sometimes|required|integer|min:0|max:999';
            $baseRules['incluir_en_promedio'] = 'sometimes|required|boolean';
            $baseRules['incluir_en_reporte_mined'] = 'sometimes|required|boolean';
            $baseRules['incluir_horario'] = 'sometimes|required|boolean';
            $baseRules['incluir_boletin'] = 'sometimes|required|boolean';
            $baseRules['mostrar_escala'] = 'sometimes|required|boolean';
            $baseRules['tipo_evaluacion'] = 'sometimes|required|in:promedio,sumativa';
            $baseRules['es_para_educacion_iniciativa'] = 'sometimes|required|boolean';
            $baseRules['permitir_copia'] = 'sometimes|required|boolean';
            $baseRules['incluir_plan_clase'] = 'sometimes|required|boolean';
        }

        return array_merge($baseRules, $corteRules, $paramRules, $hijasRules);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors' => $validator->errors()
        ], 422));
    }
}
