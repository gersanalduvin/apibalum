<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigPlanPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'estado' => 'sometimes|boolean',
            'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
        ];

        // Reglas específicas para actualización
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['nombre'] = 'sometimes|required|string|max:255';
            $rules['periodo_lectivo_id'] = 'sometimes|required|integer|exists:conf_periodo_lectivos,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del plan de pago es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres',
            'estado.boolean' => 'El estado debe ser verdadero o falso',
            'periodo_lectivo_id.required' => 'El período lectivo es obligatorio',
            'periodo_lectivo_id.integer' => 'El período lectivo debe ser un número entero',
            'periodo_lectivo_id.exists' => 'El período lectivo seleccionado no existe',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convertir estado a boolean si viene como string
        if ($this->has('estado')) {
            $this->merge([
                'estado' => filter_var($this->estado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
            ]);
        } else {
            // Si no se proporciona estado, establecer como activo por defecto
            $this->merge(['estado' => true]);
        }

        // Convertir periodo_lectivo_id a entero
        if ($this->has('periodo_lectivo_id')) {
            $this->merge([
                'periodo_lectivo_id' => (int) $this->periodo_lectivo_id
            ]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors' => $validator->errors()
        ], 422));
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Limpiar datos innecesarios
        $cleanData = array_filter($validated, function($value) {
            return $value !== null && $value !== '';
        });

        return $key ? ($cleanData[$key] ?? $default) : $cleanData;
    }
}