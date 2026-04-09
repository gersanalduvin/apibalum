<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigArqueoRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $base = [
            'fecha' => 'required|date',
            'totalc' => 'required|numeric',
            'totald' => 'required|numeric',
            'tasacambio' => 'required|numeric',
            'totalarqueo' => 'required|numeric'
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $base = [
                'fecha' => 'sometimes|date',
                'totalc' => 'sometimes|numeric',
                'totald' => 'sometimes|numeric',
                'tasacambio' => 'sometimes|numeric',
                'totalarqueo' => 'sometimes|numeric'
            ];
        }
        return $base;
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['success'=>false,'message'=>'Errores de validación','errors'=>$validator->errors()],422));
    }
}

