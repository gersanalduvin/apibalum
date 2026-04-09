<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NotMateriasAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nombre' => 'required|string|max:180',
            'orden' => 'required|integer|min:0|max:99',
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = [
                'nombre' => 'sometimes|required|string|max:180',
                'orden' => 'sometimes|required|integer|min:0|max:99',
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

