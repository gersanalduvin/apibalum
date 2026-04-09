<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignacionDocenteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required','integer','exists:users,id',
                Rule::exists('users','id')->where('tipo_usuario','docente'),
            ],
            'asignatura_grado_id' => 'required|integer|exists:not_asignatura_grado,id',
            'grupo_id' => 'required|integer|exists:config_grupos,id',
            'permiso_fecha_corte1' => 'nullable|date',
            'permiso_fecha_corte2' => 'nullable|date',
            'permiso_fecha_corte3' => 'nullable|date',
            'permiso_fecha_corte4' => 'nullable|date',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}

