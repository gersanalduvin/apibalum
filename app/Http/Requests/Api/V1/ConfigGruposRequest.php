<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigGruposRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grado_id' => [
                'required',
                'integer',
                'exists:config_grado,id'
            ],
            'seccion_id' => [
                'required',
                'integer',
                'exists:config_seccion,id'
            ],
            'turno_id' => [
                'required',
                'integer',
                'exists:config_turnos,id'
            ],
            'docente_guia' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'periodo_lectivo_id' => [
                'required',
                'integer',
                'exists:conf_periodo_lectivos,id'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'grado_id.required' => 'El grado es obligatorio',
            'grado_id.integer' => 'El grado debe ser un número entero',
            'grado_id.exists' => 'El grado seleccionado no existe',
            
            'seccion_id.required' => 'La sección es obligatoria',
            'seccion_id.integer' => 'La sección debe ser un número entero',
            'seccion_id.exists' => 'La sección seleccionada no existe',
            
            'turno_id.required' => 'El turno es obligatorio',
            'turno_id.integer' => 'El turno debe ser un número entero',
            'turno_id.exists' => 'El turno seleccionado no existe',
            
            
            'docente_guia.integer' => 'El docente guía debe ser un número entero',
            'docente_guia.exists' => 'El docente guía seleccionado no existe',
            
            'periodo_lectivo_id.required' => 'El período lectivo es obligatorio',
            'periodo_lectivo_id.integer' => 'El período lectivo debe ser un número entero',
            'periodo_lectivo_id.exists' => 'El período lectivo seleccionado no existe',
        ];
    }

    public function attributes(): array
    {
        return [
            'grado_id' => 'grado',
            'seccion_id' => 'sección',
            'turno_id' => 'turno',
            // modalidad removida del modelo de grupos
            'docente_guia' => 'docente guía',
            'periodo_lectivo_id' => 'período lectivo',
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
        
        if ($this->has('grado_id')) {
            $data['grado_id'] = (int) $this->grado_id;
        }
        
        if ($this->has('seccion_id')) {
            $data['seccion_id'] = (int) $this->seccion_id;
        }
        
        if ($this->has('turno_id')) {
            $data['turno_id'] = (int) $this->turno_id;
        }
        
        
        if ($this->has('docente_guia') && $this->docente_guia !== null) {
            $data['docente_guia'] = (int) $this->docente_guia;
        }
        
        if ($this->has('periodo_lectivo_id')) {
            $data['periodo_lectivo_id'] = (int) $this->periodo_lectivo_id;
        }
        
        $this->merge($data);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validación personalizada para verificar unicidad de la configuración del grupo
            $gradoId = $this->input('grado_id');
            $seccionId = $this->input('seccion_id');
            $turnoId = $this->input('turno_id');
            $periodoLectivoId = $this->input('periodo_lectivo_id');
            
            // Verificar si ya existe un grupo con esta configuración
            $query = \App\Models\ConfigGrupos::where([
                'grado_id' => $gradoId,
                'seccion_id' => $seccionId,
                'turno_id' => $turnoId,
                'periodo_lectivo_id' => $periodoLectivoId
            ]);
            
            // Si es una actualización, excluir el registro actual
            if ($this->route('id')) {
                $query->where('id', '!=', $this->route('id'));
            }
            
            if ($query->exists()) {
                $validator->errors()->add('configuracion', 'Ya existe un grupo con esta configuración (grado, sección, turno y período lectivo)');
            }
        });
    }
}