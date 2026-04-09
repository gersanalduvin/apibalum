<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NotMateriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nombre' => 'required|string|max:180',
            'abreviatura' => 'required|string|max:10',
            'materia_id' => 'required|integer|exists:not_materias_areas,id',
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = [
                'nombre' => 'sometimes|required|string|max:180',
                'abreviatura' => 'sometimes|required|string|max:10',
                'materia_id' => 'sometimes|required|integer|exists:not_materias_areas,id',
            ];
        }
        return $rules;
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
