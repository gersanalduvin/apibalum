<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigFormaPagoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'abreviatura' => 'required|string|max:10',
            'activo' => 'boolean',
            'es_efectivo' => 'boolean',
            'moneda' => 'boolean',
        ];

        // Si es una actualización (método PUT/PATCH), hacer los campos opcionales
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = [
                'nombre' => 'sometimes|required|string|max:255',
                'abreviatura' => 'sometimes|required|string|max:10',
                'activo' => 'sometimes|boolean',
                'es_efectivo' => 'sometimes|boolean',
                'moneda' => 'sometimes|boolean',
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres',
            'abreviatura.required' => 'La abreviatura es obligatoria',
            'abreviatura.string' => 'La abreviatura debe ser una cadena de texto',
            'abreviatura.max' => 'La abreviatura no puede tener más de 10 caracteres',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso',
            'es_efectivo.boolean' => 'El campo es_efectivo debe ser verdadero o falso',
            'moneda.boolean' => 'El campo moneda debe ser verdadero o falso',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'abreviatura' => 'abreviatura',
            'activo' => 'activo',
            'es_efectivo' => 'es_efectivo',
            'moneda' => 'moneda',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Errores de validación',
            'errors' => $validator->errors()
        ], 422));
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Convertir activo a boolean si viene como string
        if ($this->has('activo')) {
            $this->merge([
                'activo' => filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
            ]);
        }

        // Si no se proporciona activo, establecer como true por defecto
        if (!$this->has('activo')) {
            $this->merge([
                'activo' => true
            ]);
        }

        if ($this->has('es_efectivo')) {
            $this->merge([
                'es_efectivo' => filter_var($this->es_efectivo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            ]);
        }

        if (!$this->has('es_efectivo')) {
            $this->merge([
                'es_efectivo' => false
            ]);
        }

        if ($this->has('moneda')) {
            $this->merge([
                'moneda' => filter_var($this->moneda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            ]);
        }

        if (!$this->has('moneda')) {
            $this->merge([
                'moneda' => false
            ]);
        }

        // Limpiar espacios en blanco de nombre y abreviatura
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre)
            ]);
        }

        if ($this->has('abreviatura')) {
            $this->merge([
                'abreviatura' => trim(strtoupper($this->abreviatura))
            ]);
        }
    }
}
