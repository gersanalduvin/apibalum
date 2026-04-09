<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigTurnosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $turnoId = $this->route('id');
        
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'unique:config_turnos,nombre' . ($turnoId ? ',' . $turnoId : '')
            ],
            'orden' => [
                'required',
                'integer',
                'min:1',
                'unique:config_turnos,orden' . ($turnoId ? ',' . $turnoId : '')
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del turno es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres',
            'nombre.unique' => 'Ya existe un turno con este nombre',
            
            'orden.required' => 'El orden del turno es obligatorio',
            'orden.integer' => 'El orden debe ser un número entero',
            'orden.min' => 'El orden debe ser mayor a 0',
            'orden.unique' => 'Ya existe un turno con este orden',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del turno',
            'orden' => 'orden del turno',
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
            $data['nombre'] = trim($this->nombre);
        }
        
        if ($this->has('orden')) {
            $data['orden'] = (int) $this->orden;
        }
        
        $this->merge($data);
    }
}