<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigArqueoMonedaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $base = [
            'moneda' => 'required|boolean',
            'denominacion' => 'required|string|max:100',
            'multiplicador' => 'required|numeric',
            'orden' => 'required|integer'
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $base = [
                'moneda' => 'sometimes|boolean',
                'denominacion' => 'sometimes|string|max:100',
                'multiplicador' => 'sometimes|numeric',
                'orden' => 'sometimes|integer'
            ];
        }
        return $base;
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['success'=>false,'message'=>'Errores de validación','errors'=>$validator->errors()],422));
    }
}

