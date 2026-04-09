<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigNotSemestreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $parcialRules = [
            'parciales' => 'required|array|min:1',
            'parciales.*.id' => 'sometimes|integer|exists:config_not_semestre_parciales,id',
            'parciales.*.nombre' => 'required|string|max:180',
            'parciales.*.abreviatura' => 'required|string|max:10',
            'parciales.*.fecha_inicio_corte' => 'required|date',
            'parciales.*.fecha_fin_corte' => 'required|date',
            'parciales.*.fecha_inicio_publicacion_notas' => 'required|date',
            'parciales.*.fecha_fin_publicacion_notas' => 'required|date',
            'parciales.*.orden' => 'required|integer|min:0|max:999',
        ];

        $baseRules = [
            'id' => 'sometimes|integer|exists:config_not_semestre,id',
            'nombre' => 'required|string|max:180',
            'abreviatura' => 'required|string|max:180',
            'orden' => 'required|integer|min:0|max:999',
            'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $baseRules['nombre'] = 'sometimes|required|string|max:180';
            $baseRules['abreviatura'] = 'sometimes|required|string|max:180';
            $baseRules['orden'] = 'sometimes|required|integer|min:0|max:999';
            $baseRules['periodo_lectivo_id'] = 'sometimes|required|integer|exists:conf_periodo_lectivos,id';
        }

        return array_merge($baseRules, $parcialRules);
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

