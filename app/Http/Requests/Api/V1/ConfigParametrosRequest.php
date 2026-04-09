<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigParametrosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consecutivo_recibo_oficial' => 'required|integer|min:1',
            'consecutivo_recibo_interno' => 'required|integer|min:1',
            'tasa_cambio_dolar' => 'required|numeric|min:0|max:999999.9999',
            'terminal_separada' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'consecutivo_recibo_oficial.required' => 'El consecutivo de recibo oficial es obligatorio',
            'consecutivo_recibo_oficial.integer' => 'El consecutivo de recibo oficial debe ser un número entero',
            'consecutivo_recibo_oficial.min' => 'El consecutivo de recibo oficial debe ser mayor a 0',
            
            'consecutivo_recibo_interno.required' => 'El consecutivo de recibo interno es obligatorio',
            'consecutivo_recibo_interno.integer' => 'El consecutivo de recibo interno debe ser un número entero',
            'consecutivo_recibo_interno.min' => 'El consecutivo de recibo interno debe ser mayor a 0',
            
            'tasa_cambio_dolar.required' => 'La tasa de cambio del dólar es obligatoria',
            'tasa_cambio_dolar.numeric' => 'La tasa de cambio del dólar debe ser un número',
            'tasa_cambio_dolar.min' => 'La tasa de cambio del dólar debe ser mayor o igual a 0',
            'tasa_cambio_dolar.max' => 'La tasa de cambio del dólar no puede exceder 999999.9999',
            
            'terminal_separada.required' => 'El campo terminal separada es obligatorio',
            'terminal_separada.boolean' => 'El campo terminal separada debe ser verdadero o falso',
        ];
    }

    public function attributes(): array
    {
        return [
            'consecutivo_recibo_oficial' => 'consecutivo de recibo oficial',
            'consecutivo_recibo_interno' => 'consecutivo de recibo interno',
            'tasa_cambio_dolar' => 'tasa de cambio del dólar',
            'terminal_separada' => 'terminal separada',
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