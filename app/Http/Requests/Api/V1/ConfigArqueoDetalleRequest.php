<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigArqueoDetalleRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $base = [
            'arqueo_id' => 'required|integer|exists:config_arqueo,id',
            'moneda_id' => 'required|integer|exists:config_arqueo_moneda,id',
            'cantidad' => 'required|numeric',
            'total' => 'required|numeric'
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $base = [
                'arqueo_id' => 'sometimes|integer|exists:config_arqueo,id',
                'moneda_id' => 'sometimes|integer|exists:config_arqueo_moneda,id',
                'cantidad' => 'sometimes|numeric',
                'total' => 'sometimes|numeric'
            ];
        }
        return $base;
    }
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['success'=>false,'message'=>'Errores de validación','errors'=>$validator->errors()],422));
    }
}

