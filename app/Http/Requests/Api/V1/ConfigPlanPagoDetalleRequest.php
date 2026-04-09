<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ConfigPlanPagoDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mesesValidos = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];

        $rules = [
            // Campos requeridos
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'importe' => 'required|numeric|min:0.01|max:999999.99', // Mayor que cero
            'moneda' => 'required|boolean',

            // Campos opcionales
            'plan_pago_id' => 'nullable|integer|exists:config_plan_pago,id',
            'cuenta_debito_id' => 'nullable|integer|exists:config_catalogo_cuentas,id',
            'cuenta_credito_id' => 'nullable|integer|exists:config_catalogo_cuentas,id',
            'cuenta_recargo_id' => 'nullable|integer|exists:config_catalogo_cuentas,id',
            'es_colegiatura' => 'nullable|boolean',
            'asociar_mes' => ['nullable', Rule::in($mesesValidos)],
            'orden_mes' => 'nullable|integer|min:0|max:12',
            'fecha_vencimiento' => 'nullable|date',
            'importe_recargo' => 'nullable|numeric|min:0|max:999999.99',
            'tipo_recargo' => ['nullable', Rule::in(['fijo', 'porcentaje'])],
        ];

        // Reglas específicas para actualización
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['codigo'] = 'sometimes|required|string|max:50';
            $rules['nombre'] = 'sometimes|required|string|max:255';
            $rules['importe'] = 'sometimes|required|numeric|min:0.01|max:999999.99'; // Mayor que cero
            $rules['moneda'] = 'sometimes|required|boolean';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            // Mensajes para campos requeridos
            'codigo.required' => 'El código es obligatorio',
            'codigo.string' => 'El código debe ser una cadena de texto',
            'codigo.max' => 'El código no puede exceder 50 caracteres',
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'importe.required' => 'El importe es obligatorio',
            'importe.numeric' => 'El importe debe ser un número',
            'importe.min' => 'El importe debe ser mayor que cero',
            'importe.max' => 'El importe no puede exceder 999,999.99',
            'moneda.required' => 'La moneda es obligatoria',
            'moneda.boolean' => 'La moneda debe ser verdadero o falso',

            // Mensajes para campos opcionales
            'plan_pago_id.integer' => 'El plan de pago debe ser un número entero',
            'plan_pago_id.exists' => 'El plan de pago seleccionado no existe',
            'cuenta_debito_id.integer' => 'La cuenta de débito debe ser un número entero',
            'cuenta_debito_id.exists' => 'La cuenta de débito seleccionada no existe',
            'cuenta_credito_id.integer' => 'La cuenta de crédito debe ser un número entero',
            'cuenta_credito_id.exists' => 'La cuenta de crédito seleccionada no existe',
            'cuenta_recargo_id.integer' => 'La cuenta de recargo debe ser un número entero',
            'cuenta_recargo_id.exists' => 'La cuenta de recargo seleccionada no existe',
            'es_colegiatura.boolean' => 'El campo es colegiatura debe ser verdadero o falso',
            'asociar_mes.in' => 'El mes asociado debe ser un mes válido (enero-diciembre)',
            'orden_mes.integer' => 'El orden del mes debe ser un número entero',
            'orden_mes.min' => 'El orden del mes debe ser mayor o igual a 0',
            'orden_mes.max' => 'El orden del mes debe ser menor o igual a 12',
            'fecha_vencimiento.date' => 'La fecha de vencimiento debe ser una fecha válida',
            'importe_recargo.numeric' => 'El importe de recargo debe ser un número',
            'importe_recargo.min' => 'El importe de recargo debe ser mayor o igual a 0',
            'importe_recargo.max' => 'El importe de recargo no puede exceder 999,999.99',
            'tipo_recargo.in' => 'El tipo de recargo debe ser "fijo" o "porcentaje"',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convertir es_colegiatura a boolean si viene como string
        if ($this->has('es_colegiatura')) {
            $this->merge([
                'es_colegiatura' => filter_var($this->es_colegiatura, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            ]);
        } else {
            $this->merge(['es_colegiatura' => false]);
        }

        // Convertir moneda a boolean si viene como string
        if ($this->has('moneda')) {
            $this->merge([
                'moneda' => filter_var($this->moneda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            ]);
        } else {
            $this->merge(['moneda' => false]);
        }

        // Convertir IDs a enteros
        if ($this->has('plan_pago_id')) {
            $this->merge(['plan_pago_id' => (int) $this->plan_pago_id]);
        }

        if ($this->has('cuenta_debito_id')) {
            $this->merge(['cuenta_debito_id' => (int) $this->cuenta_debito_id]);
        }

        if ($this->has('cuenta_credito_id')) {
            $this->merge(['cuenta_credito_id' => (int) $this->cuenta_credito_id]);
        }

        if ($this->has('cuenta_recargo_id')) {
            $this->merge(['cuenta_recargo_id' => (int) $this->cuenta_recargo_id]);
        }

        // Convertir importes a float
        if ($this->has('importe')) {
            $this->merge(['importe' => (float) $this->importe]);
        }

        if ($this->has('importe_recargo')) {
            $this->merge(['importe_recargo' => (float) $this->importe_recargo]);
        }

        // Normalizar mes asociado
        if ($this->has('asociar_mes')) {
            if ($this->asociar_mes === null || $this->asociar_mes === '') {
                // Mantener null o string vacío como están
                $this->merge(['asociar_mes' => $this->asociar_mes]);
            } else {
                // Solo normalizar si tiene un valor válido
                $this->merge(['asociar_mes' => strtolower(trim($this->asociar_mes))]);
            }
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que si hay importe_recargo, debe haber tipo_recargo

            // Validar que si hay tipo_recargo, debe haber importe_recargo
            if ($this->filled('tipo_recargo') && !$this->filled('importe_recargo')) {
                $validator->errors()->add('importe_recargo', 'El importe de recargo es obligatorio cuando se especifica un tipo de recargo');
            }

            // Validar que las cuentas de débito y crédito sean diferentes (solo si ambas están presentes)
            if ($this->filled('cuenta_debito_id') && $this->filled('cuenta_credito_id')) {
                if ($this->cuenta_debito_id === $this->cuenta_credito_id) {
                    $validator->errors()->add('cuenta_credito_id', 'La cuenta de crédito debe ser diferente a la cuenta de débito');
                }
            }

            // Validar porcentaje de recargo
            if ($this->filled('tipo_recargo') && $this->tipo_recargo === 'porcentaje' && $this->filled('importe_recargo')) {
                if ($this->importe_recargo > 100) {
                    $validator->errors()->add('importe_recargo', 'El porcentaje de recargo no puede ser mayor a 100%');
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

    public function validated($key = null, $default = null)
    {
        // Obtener datos validados del padre
        $validated = parent::validated($key, $default);

        // Si se solicita una clave específica, devolverla directamente
        if ($key !== null) {
            return $validated;
        }

        // Si no hay datos validados, retornar array vacío
        if (!is_array($validated)) {
            return [];
        }

        // Limpiar datos innecesarios, pero mantener asociar_mes si está presente explícitamente
        $cleanData = [];
        
        foreach ($validated as $fieldKey => $value) {
            // Mantener valores booleanos false y números 0
            if (in_array($fieldKey, ['es_colegiatura', 'moneda']) && is_bool($value)) {
                $cleanData[$fieldKey] = $value;
                continue;
            }
            
            if (in_array($fieldKey, ['importe', 'importe_recargo']) && is_numeric($value)) {
                $cleanData[$fieldKey] = $value;
                continue;
            }
            
            // Permitir que asociar_mes mantenga valores null explícitos
            if ($fieldKey === 'asociar_mes') {
                $cleanData[$fieldKey] = $value;
                continue;
            }
            
            // Para otros campos, solo incluir si no son null o vacíos
            if ($value !== null && $value !== '') {
                $cleanData[$fieldKey] = $value;
            }
        }

        return $cleanData;
    }
}
