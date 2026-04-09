<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfPeriodoLectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    public function rules(): array
    {
        $rules = [
            'nombre' => 'required|string|max:255|unique:conf_periodo_lectivos,nombre',
            'prefijo_alumno' => 'required|string|max:10',
            'prefijo_docente' => 'required|string|max:10',
            'prefijo_familia' => 'required|string|max:10',
            'prefijo_admin' => 'required|string|max:10',
            'incremento_alumno' => 'required|integer|min:1',
            'incremento_docente' => 'required|integer|min:1',
            'incremento_familia' => 'required|integer|min:1',
            'periodo_nota' => 'sometimes|boolean',
            'periodo_matricula' => 'sometimes|boolean',
        ];
        
        // Para actualización, hacer campos opcionales y manejar unique
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach ($rules as $field => $rule) {
                $rules[$field] = str_replace('required|', 'sometimes|', $rule);
            }
            
            // Para actualización, ignorar el registro actual en la validación unique
            $id = $this->route('id') ?? $this->route('conf_periodo_lectivo');
            if ($id) {
                $rules['nombre'] = str_replace('unique:conf_periodo_lectivos,nombre', 'unique:conf_periodo_lectivos,nombre,' . $id, $rules['nombre']);
            }
        }
        
        return $rules;
    }
    
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'nombre.unique' => 'Ya existe un período lectivo con este nombre',
            
            'prefijo_alumno.required' => 'El prefijo de alumno es obligatorio',
            'prefijo_alumno.string' => 'El prefijo de alumno debe ser una cadena de texto',
            'prefijo_alumno.max' => 'El prefijo de alumno no puede exceder 10 caracteres',
            
            'prefijo_docente.required' => 'El prefijo de docente es obligatorio',
            'prefijo_docente.string' => 'El prefijo de docente debe ser una cadena de texto',
            'prefijo_docente.max' => 'El prefijo de docente no puede exceder 10 caracteres',
            
            'prefijo_familia.required' => 'El prefijo de familia es obligatorio',
            'prefijo_familia.string' => 'El prefijo de familia debe ser una cadena de texto',
            'prefijo_familia.max' => 'El prefijo de familia no puede exceder 10 caracteres',
            
            'prefijo_admin.required' => 'El prefijo de administrador es obligatorio',
            'prefijo_admin.string' => 'El prefijo de administrador debe ser una cadena de texto',
            'prefijo_admin.max' => 'El prefijo de administrador no puede exceder 10 caracteres',
            
            'incremento_alumno.required' => 'El incremento de alumno es obligatorio',
            'incremento_alumno.integer' => 'El incremento de alumno debe ser un número entero',
            'incremento_alumno.min' => 'El incremento de alumno debe ser mayor a 0',
            
            'incremento_docente.required' => 'El incremento de docente es obligatorio',
            'incremento_docente.integer' => 'El incremento de docente debe ser un número entero',
            'incremento_docente.min' => 'El incremento de docente debe ser mayor a 0',
            
            'incremento_familia.required' => 'El incremento de familia es obligatorio',
            'incremento_familia.integer' => 'El incremento de familia debe ser un número entero',
            'incremento_familia.min' => 'El incremento de familia debe ser mayor a 0',
            
            'periodo_nota.boolean' => 'El período de nota debe ser verdadero o falso',
            'periodo_matricula.boolean' => 'El período de matrícula debe ser verdadero o falso',
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