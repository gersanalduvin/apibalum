<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UsersGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'user_id' => 'required|integer|exists:users,id',
            'fecha_matricula' => 'required|date|before_or_equal:today',
            'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
            'grado_id' => 'required|integer|exists:config_grado,id',
            'grupo_id' => 'nullable|integer|exists:config_grupos,id',
            'turno_id' => 'required|integer|exists:config_turnos,id',
            'numero_recibo' => 'nullable|string|max:255',
            'tipo_ingreso' => ['required', Rule::in(['reingreso', 'nuevo_ingreso', 'traslado'])],
            'estado' => ['required', Rule::in(['activo', 'no_activo', 'retiro_anticipado'])],
            'activar_estadistica' => 'boolean',
            'corte_retiro' => [
                'nullable',
                Rule::in(['corte1', 'corte2', 'corte3', 'corte4']),
                'required_if:estado,retiro_anticipado'
            ],
            'corte_ingreso' => [
                'nullable',
                Rule::in(['corte1', 'corte2', 'corte3', 'corte4'])
            ],
            'maestra_anterior' => 'nullable|string|max:255'
        ];

        // Reglas específicas para actualización
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Para actualización, algunos campos pueden ser opcionales
            $rules['user_id'] = 'sometimes|integer|exists:users,id';
            $rules['fecha_matricula'] = 'sometimes|date|before_or_equal:today';
            $rules['periodo_lectivo_id'] = 'sometimes|integer|exists:conf_periodo_lectivos,id';
            $rules['grado_id'] = 'sometimes|integer|exists:config_grado,id';
            $rules['turno_id'] = 'sometimes|integer|exists:config_turnos,id';
            $rules['tipo_ingreso'] = ['sometimes', Rule::in(['reingreso', 'nuevo_ingreso', 'traslado'])];
            $rules['estado'] = ['sometimes', Rule::in(['activo', 'no_activo', 'retiro_anticipado'])];
            $rules['maestra_anterior'] = 'sometimes|nullable|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'El usuario es obligatorio',
            'user_id.exists' => 'El usuario seleccionado no existe',
            'fecha_matricula.required' => 'La fecha de matrícula es obligatoria',
            'fecha_matricula.date' => 'La fecha de matrícula debe ser una fecha válida',
            'fecha_matricula.before_or_equal' => 'La fecha de matrícula no puede ser posterior a hoy',
            'periodo_lectivo_id.required' => 'El período lectivo es obligatorio',
            'periodo_lectivo_id.exists' => 'El período lectivo seleccionado no existe',
            'grado_id.required' => 'El grado es obligatorio',
            'grado_id.exists' => 'El grado seleccionado no existe',
            'grupo_id.exists' => 'El grupo seleccionado no existe',
            'turno_id.required' => 'El turno es obligatorio',
            'turno_id.exists' => 'El turno seleccionado no existe',
            'numero_recibo.string' => 'El número de recibo debe ser texto',
            'numero_recibo.max' => 'El número de recibo no puede exceder 255 caracteres',
            'tipo_ingreso.required' => 'El tipo de ingreso es obligatorio',
            'tipo_ingreso.in' => 'El tipo de ingreso debe ser: reingreso, nuevo_ingreso o traslado',
            'estado.required' => 'El estado es obligatorio',
            'estado.in' => 'El estado debe ser: activo, no_activo o retiro_anticipado',
            'activar_estadistica.boolean' => 'Activar estadística debe ser verdadero o falso',
            'corte_retiro.in' => 'El corte de retiro debe ser: corte1, corte2, corte3 o corte4',
            'corte_retiro.required_if' => 'El corte de retiro es obligatorio cuando el estado es retiro anticipado',
            'corte_ingreso.in' => 'El corte de ingreso debe ser: corte1, corte2, corte3 o corte4'
            ,
            'maestra_anterior.string' => 'La maestra anterior debe ser texto',
            'maestra_anterior.max' => 'La maestra anterior no puede exceder 255 caracteres'
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que si activar_estadistica es true, se requieren los cortes
            if ($this->input('activar_estadistica') === true) {
                if ($this->input('estado') === 'retiro_anticipado' && !$this->input('corte_retiro')) {
                    $validator->errors()->add('corte_retiro', 'El corte de retiro es obligatorio cuando la estadística está activada y el estado es retiro anticipado');
                }
            }

            // Validar que no se duplique la matrícula del usuario en el mismo período
            if ($this->input('user_id') && $this->input('periodo_lectivo_id')) {
                $query = \App\Models\UsersGrupo::where('user_id', $this->input('user_id'))
                    ->where('periodo_lectivo_id', $this->input('periodo_lectivo_id'));

                // Si es actualización, excluir el registro actual
                if ($this->route('id')) {
                    $query->where('id', '!=', $this->route('id'));
                }

                if ($query->exists()) {
                    $validator->errors()->add('user_id', 'El usuario ya está matriculado en este período lectivo');
                }
            }

            // Validar que si no se activa estadística, no se permitan cortes
            if ($this->input('activar_estadistica') === false) {
                if ($this->input('corte_retiro')) {
                    $validator->errors()->add('corte_retiro', 'No se puede especificar corte de retiro si la estadística no está activada');
                }
                if ($this->input('corte_ingreso')) {
                    $validator->errors()->add('corte_ingreso', 'No se puede especificar corte de ingreso si la estadística no está activada');
                }
            }
        });
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
        // Convertir strings a boolean si es necesario
        if ($this->has('activar_estadistica')) {
            $this->merge([
                'activar_estadistica' => filter_var($this->activar_estadistica, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        // Limpiar cortes si activar_estadistica es false
        if ($this->input('activar_estadistica') === false) {
            $this->merge([
                'corte_retiro' => null,
                'corte_ingreso' => null
            ]);
        }

        // Convertir fecha_matricula al formato correcto (Y-m-d) si viene con timezone
        if ($this->has('fecha_matricula')) {
            $fecha = $this->input('fecha_matricula');
            if ($fecha && is_string($fecha)) {
                try {
                    // Si la fecha viene con timezone, convertirla a formato Y-m-d
                    $fechaObj = new \DateTime($fecha);
                    $this->merge([
                        'fecha_matricula' => $fechaObj->format('Y-m-d')
                    ]);
                } catch (\Exception $e) {
                    // Si no se puede parsear, dejar el valor original para que la validación lo maneje
                }
            }
        }
    }
}
