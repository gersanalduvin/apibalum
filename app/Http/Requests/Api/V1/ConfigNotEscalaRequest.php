<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigNotEscalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $detalleRules = [
            'detalles' => 'required|array|min:1',
            'detalles.*.id' => 'sometimes|integer|exists:config_not_escala_detalle,id',
            'detalles.*.nombre' => 'required|string|max:180',
            'detalles.*.abreviatura' => 'nullable|string|max:10',
            'detalles.*.rango_inicio' => 'required|integer|min:0|max:999',
            'detalles.*.rango_fin' => 'required|integer|min:0|max:999',
            'detalles.*.orden' => 'nullable|integer|min:0|max:999',
        ];

        $baseRules = [
            'id' => 'sometimes|integer|exists:config_not_escala,id',
            'nombre' => 'required|string|max:180',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $baseRules['nombre'] = 'sometimes|required|string|max:180';
        }

        return array_merge($baseRules, $detalleRules);
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

