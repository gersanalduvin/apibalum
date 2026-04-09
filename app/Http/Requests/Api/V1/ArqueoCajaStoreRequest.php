<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ArqueoCajaStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'fecha' => 'required|date',
            'tasacambio' => 'required|numeric',
            'detalles' => 'required|array|min:1',
            'detalles.*.moneda_id' => 'required|integer|exists:config_arqueo_moneda,id',
            'detalles.*.cantidad' => 'required|numeric|min:0'
        ];
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['success'=>false,'message'=>'Errores de validación','errors'=>$validator->errors()],422));
    }
}

