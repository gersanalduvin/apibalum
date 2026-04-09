<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigSeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $seccionId = $this->route('id');
        
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'unique:config_seccion,nombre' . ($seccionId ? ',' . $seccionId : '')
            ],
            'orden' => [
                'required',
                'integer',
                'min:1',
                'unique:config_seccion,orden' . ($seccionId ? ',' . $seccionId : '')
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la sección es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres',
            'nombre.unique' => 'Ya existe una sección con este nombre',
            
            'orden.required' => 'El orden de la sección es obligatorio',
            'orden.integer' => 'El orden debe ser un número entero',
            'orden.min' => 'El orden debe ser mayor a 0',
            'orden.unique' => 'Ya existe una sección con este orden',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la sección',
            'orden' => 'orden de la sección',
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

    protected function prepareForValidation(): void
    {
        $data = [];
        
        if ($this->has('nombre')) {
            $data['nombre'] = strtoupper(trim($this->nombre));
        }
        
        if ($this->has('orden')) {
            $data['orden'] = (int) $this->orden;
        }
        
        $this->merge($data);
    }
}