<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrganizarListasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_lectivo_id' => 'nullable|integer|exists:conf_periodo_lectivos,id',
            'grado_id' => 'nullable|integer|exists:config_grado,id',
            'turno_id' => 'nullable|integer|exists:config_turnos,id',
            'alumnos' => 'nullable|array|min:1',
            'alumnos.*' => 'integer|exists:users,id',
            'grupo_id' => 'nullable|integer|exists:config_grupos,id',
            'asignaciones' => 'nullable|array|min:1',
            'asignaciones.*.user_id' => 'required_with:asignaciones|integer|exists:users,id',
            'asignaciones.*.grupo_id' => 'required_with:asignaciones|integer|exists:config_grupos,id'
        ];
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