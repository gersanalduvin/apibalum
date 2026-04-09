<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ConfigCatalogoCuentasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];
        $cuentaId = $this->route('id');

        switch ($this->method()) {
            case 'POST':
                $rules = $this->createRules();
                break;
            case 'PUT':
            case 'PATCH':
                $rules = $this->updateRules($cuentaId);
                break;
        }

        return $rules;
    }

    private function createRules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                'unique:config_catalogo_cuentas,codigo',
                'regex:/^[0-9]+(\.[0-9]+)*$/'
            ],
            'nombre' => [
                'required',
                'string',
                'max:150'
            ],
            'tipo' => [
                'required',
                Rule::in(['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto'])
            ],
            'nivel' => [
                'sometimes',
                'integer',
                'min:1',
                'max:10'
            ],
            'padre_id' => [
                'nullable',
                'integer',
                'exists:config_catalogo_cuentas,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $this->validatePadreCompatibility($value, $fail);
                    }
                }
            ],
            'es_grupo' => [
                'required',
                'boolean'
            ],
            'permite_movimiento' => [
                'required',
                'boolean',
                function ($attribute, $value, $fail) {
                    $esGrupo = $this->input('es_grupo');
                    if ($esGrupo && $value) {
                        $fail('Las cuentas de grupo no pueden permitir movimientos.');
                    }
                    if (!$esGrupo && !$value) {
                        $fail('Las cuentas que no son grupo deben permitir movimientos.');
                    }
                }
            ],
            'naturaleza' => [
                'required',
                Rule::in(['deudora', 'acreedora'])
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'estado' => [
                'required',
                Rule::in(['activo', 'inactivo'])
            ],
            'moneda_usd' => [
                'required',
                'boolean'
            ],
            // Campos de sincronización (opcionales)
            'uuid' => [
                'sometimes',
                'string',
                'uuid'
            ],
            'version' => [
                'sometimes',
                'integer',
                'min:1'
            ],
            'is_synced' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    private function updateRules(?int $cuentaId): array
    {
        return [
            'codigo' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('config_catalogo_cuentas', 'codigo')->ignore($cuentaId),
                'regex:/^[0-9]+(\.[0-9]+)*$/'
            ],
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:150'
            ],
            'tipo' => [
                'sometimes',
                'required',
                Rule::in(['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto'])
            ],
            'nivel' => [
                'sometimes',
                'integer',
                'min:1',
                'max:10'
            ],
            'padre_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:config_catalogo_cuentas,id',
                function ($attribute, $value, $fail) use ($cuentaId) {
                    if ($value) {
                        // No puede ser padre de sí mismo
                        if ($value == $cuentaId) {
                            $fail('Una cuenta no puede ser padre de sí misma.');
                            return;
                        }
                        
                        $this->validatePadreCompatibility($value, $fail);
                        $this->validateNoCyclicReference($value, $cuentaId, $fail);
                    }
                }
            ],
            'es_grupo' => [
                'sometimes',
                'required',
                'boolean',
                function ($attribute, $value, $fail) use ($cuentaId) {
                    // Si se está cambiando a no grupo, verificar que no tenga hijos
                    if (!$value && $cuentaId) {
                        $cuenta = \App\Models\ConfigCatalogoCuentas::find($cuentaId);
                        if ($cuenta && $cuenta->hijos()->count() > 0) {
                            $fail('No se puede cambiar a cuenta de movimiento si tiene cuentas hijas.');
                        }
                    }
                }
            ],
            'permite_movimiento' => [
                'sometimes',
                'required',
                'boolean',
                function ($attribute, $value, $fail) {
                    $esGrupo = $this->input('es_grupo');
                    if ($esGrupo !== null) {
                        if ($esGrupo && $value) {
                            $fail('Las cuentas de grupo no pueden permitir movimientos.');
                        }
                        if (!$esGrupo && !$value) {
                            $fail('Las cuentas que no son grupo deben permitir movimientos.');
                        }
                    }
                }
            ],
            'naturaleza' => [
                'sometimes',
                'required',
                Rule::in(['deudora', 'acreedora'])
            ],
            'descripcion' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000'
            ],
            'estado' => [
                'sometimes',
                'required',
                Rule::in(['activo', 'inactivo'])
            ],
            'moneda_usd' => [
                'sometimes',
                'required',
                'boolean'
            ],
            // Campos de sincronización (opcionales)
            'version' => [
                'sometimes',
                'integer',
                'min:1'
            ],
            'is_synced' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    private function validatePadreCompatibility($padreId, $fail): void
    {
        $padre = \App\Models\ConfigCatalogoCuentas::find($padreId);
        
        if (!$padre) {
            $fail('La cuenta padre no existe.');
            return;
        }

        // El padre debe ser una cuenta de grupo
        if (!$padre->es_grupo) {
            $fail('La cuenta padre debe ser una cuenta de grupo.');
            return;
        }

        // Validar compatibilidad de tipo
        $tipo = $this->input('tipo');
        if ($tipo && $tipo !== $padre->tipo) {
            $fail('El tipo de cuenta debe ser igual al de la cuenta padre.');
            return;
        }

        // Validar compatibilidad de naturaleza
        $naturaleza = $this->input('naturaleza');
        if ($naturaleza && $naturaleza !== $padre->naturaleza) {
            $fail('La naturaleza debe ser igual a la de la cuenta padre.');
            return;
        }

        // Validar compatibilidad de moneda
        $monedaUsd = $this->input('moneda_usd');
        if ($monedaUsd !== null && $monedaUsd !== $padre->moneda_usd) {
            $fail('La moneda debe ser igual a la de la cuenta padre.');
            return;
        }
    }

    private function validateNoCyclicReference($padreId, $cuentaId, $fail): void
    {
        // Verificar que no se cree una referencia cíclica
        $cuenta = \App\Models\ConfigCatalogoCuentas::find($cuentaId);
        if (!$cuenta) {
            return;
        }

        $descendientes = $cuenta->getDescendientes();
        if ($descendientes->contains('id', $padreId)) {
            $fail('No se puede establecer esta relación padre-hijo porque crearía una referencia cíclica.');
        }
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya existe.',
            'codigo.regex' => 'El código debe tener formato jerárquico (ej: 1.1.01).',
            'codigo.max' => 'El código no puede tener más de 20 caracteres.',
            
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 150 caracteres.',
            
            'tipo.required' => 'El tipo es obligatorio.',
            'tipo.in' => 'El tipo debe ser: activo, pasivo, patrimonio, ingreso o gasto.',
            
            'nivel.integer' => 'El nivel debe ser un número entero.',
            'nivel.min' => 'El nivel debe ser mayor a 0.',
            'nivel.max' => 'El nivel no puede ser mayor a 10.',
            
            'padre_id.exists' => 'La cuenta padre no existe.',
            'padre_id.integer' => 'El ID del padre debe ser un número entero.',
            
            'es_grupo.required' => 'Debe especificar si es cuenta de grupo.',
            'es_grupo.boolean' => 'El campo es_grupo debe ser verdadero o falso.',
            
            'permite_movimiento.required' => 'Debe especificar si permite movimientos.',
            'permite_movimiento.boolean' => 'El campo permite_movimiento debe ser verdadero o falso.',
            
            'naturaleza.required' => 'La naturaleza es obligatoria.',
            'naturaleza.in' => 'La naturaleza debe ser: deudora o acreedora.',
            
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser: activo o inactivo.',
            
            'moneda_usd.required' => 'Debe especificar el tipo de moneda.',
            'moneda_usd.boolean' => 'El campo moneda_usd debe ser verdadero o falso.',
            
            'uuid.uuid' => 'El UUID debe tener un formato válido.',
            'version.integer' => 'La versión debe ser un número entero.',
            'version.min' => 'La versión debe ser mayor a 0.',
            'is_synced.boolean' => 'El campo is_synced debe ser verdadero o falso.'
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
        // Limpiar y preparar datos antes de la validación
        if ($this->has('codigo')) {
            $this->merge([
                'codigo' => trim($this->codigo)
            ]);
        }

        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre)
            ]);
        }

        // Convertir strings a boolean si es necesario
        if ($this->has('es_grupo') && is_string($this->es_grupo)) {
            $this->merge([
                'es_grupo' => filter_var($this->es_grupo, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('permite_movimiento') && is_string($this->permite_movimiento)) {
            $this->merge([
                'permite_movimiento' => filter_var($this->permite_movimiento, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('moneda_usd') && is_string($this->moneda_usd)) {
            $this->merge([
                'moneda_usd' => filter_var($this->moneda_usd, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('is_synced') && is_string($this->is_synced)) {
            $this->merge([
                'is_synced' => filter_var($this->is_synced, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}