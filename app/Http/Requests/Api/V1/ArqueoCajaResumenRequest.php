<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ArqueoCajaResumenRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'fecha' => 'nullable|date',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date'
        ];
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['success'=>false,'message'=>'Errores de validación','errors'=>$validator->errors()],422));
    }
}

