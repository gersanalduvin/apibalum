<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigGradoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gradoId = $this->route('id');
        
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'unique:config_grado,nombre' . ($gradoId ? ',' . $gradoId : '')
            ],
            'abreviatura' => [
                'required',
                'string',
                'max:10',
                'unique:config_grado,abreviatura' . ($gradoId ? ',' . $gradoId : '')
            ],
            'orden' => [
                'required',
                'integer',
                'min:1',
                'unique:config_grado,orden' . ($gradoId ? ',' . $gradoId : '')
            ],
            'modalidad_id' => [
                'required',
                'integer',
                'exists:config_modalidad,id'
            ],
            'formato' => [
                'nullable',
                'string',
                'in:cuantitativo,cualitativo'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del grado es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres',
            'nombre.unique' => 'Ya existe un grado con este nombre',
            
            'abreviatura.required' => 'La abreviatura del grado es obligatoria',
            'abreviatura.string' => 'La abreviatura debe ser una cadena de texto',
            'abreviatura.max' => 'La abreviatura no puede exceder los 10 caracteres',
            'abreviatura.unique' => 'Ya existe un grado con esta abreviatura',
            
            'orden.required' => 'El orden del grado es obligatorio',
            'orden.integer' => 'El orden debe ser un número entero',
            'orden.min' => 'El orden debe ser mayor a 0',
            'orden.unique' => 'Ya existe un grado con este orden',

            'modalidad_id.required' => 'La modalidad es obligatoria',
            'modalidad_id.integer' => 'La modalidad debe ser un número entero',
            'modalidad_id.exists' => 'La modalidad seleccionada no existe',

            'formato.string' => 'El formato debe ser una cadena de texto',
            'formato.in' => 'El formato debe ser cuantitativo o cualitativo',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del grado',
            'abreviatura' => 'abreviatura del grado',
            'orden' => 'orden del grado',
            'modalidad_id' => 'modalidad',
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
        
        if ($this->has('abreviatura')) {
            $data['abreviatura'] = strtoupper(trim($this->abreviatura));
        }
        
        if ($this->has('orden')) {
            $data['orden'] = (int) $this->orden;
        }
        
        if ($this->has('modalidad_id')) {
            $data['modalidad_id'] = (int) $this->modalidad_id;
        }

        if ($this->has('formato')) {
            $data['formato'] = trim($this->formato);
        }
        
        $this->merge($data);
    }
}