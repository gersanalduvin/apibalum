<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignacionDocenteBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required','integer',
                Rule::exists('users','id')->where('tipo_usuario','docente'),
            ],
            'items' => 'required|array|min:1',
            'items.*.asignatura_grado_id' => 'required|integer|exists:not_asignatura_grado,id',
            'items.*.grupo_id' => 'required|integer|exists:config_grupos,id',
            'items.*.permiso_fecha_corte1' => 'nullable|date',
            'items.*.permiso_fecha_corte2' => 'nullable|date',
            'items.*.permiso_fecha_corte3' => 'nullable|date',
            'items.*.permiso_fecha_corte4' => 'nullable|date',
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
