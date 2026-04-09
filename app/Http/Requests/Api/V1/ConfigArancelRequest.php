<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ConfigArancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $arancelId = $this->route('id') ?? $this->route('uuid');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'codigo' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('config_aranceles', 'codigo')
                    ->ignore($arancelId, $this->route()->hasParameter('uuid') ? 'uuid' : 'id')
                    ->whereNull('deleted_at')
            ],
            'nombre' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                'min:3'
            ],
            'precio' => [
                $isUpdate ? 'sometimes' : 'required',
                'numeric',
                'min:0.01',
                'max:999999.99'
            ],
            'moneda' => [
                $isUpdate ? 'sometimes' : 'required',
                'boolean'
            ],
            'cuenta_debito_id' => [
                'nullable',
                'integer',
                'exists:config_catalogo_cuentas,id'
            ],
            'cuenta_credito_id' => [
                'nullable',
                'integer',
                'exists:config_catalogo_cuentas,id',
                'different:cuenta_debito_id'
            ],
            'activo' => [
                'sometimes',
                'boolean'
            ],
            'productos' => [
                'sometimes',
                'array'
            ],
            'productos.*.producto_id' => [
                'required',
                'exists:inventario_producto,id'
            ],
            'productos.*.cantidad' => [
                'required',
                'numeric',
                'min:0.01'
            ]
        ];

        // Reglas específicas para búsqueda
        if ($this->isMethod('GET') && $this->route()->getName() === 'config-aranceles.search') {
            return [
                'codigo' => 'sometimes|string|max:20',
                'nombre' => 'sometimes|string|max:255',
                'moneda' => 'sometimes|boolean',
                'activo' => 'sometimes|boolean',
                'precio_min' => 'sometimes|numeric|min:0',
                'precio_max' => 'sometimes|numeric|min:0',
                'per_page' => 'sometimes|integer|min:1|max:100'
            ];
        }

        // Reglas para sincronización
        if ($this->isMethod('POST') && $this->route()->getName() === 'config-aranceles.mark-synced') {
            return [
                'uuids' => 'required|array|min:1',
                'uuids.*' => 'required|string|uuid'
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio',
            'codigo.string' => 'El código debe ser una cadena de texto',
            'codigo.max' => 'El código no puede tener más de 20 caracteres',
            'codigo.regex' => 'El código solo puede contener letras mayúsculas, números, guiones y guiones bajos',
            'codigo.unique' => 'Ya existe un arancel con este código',

            'nombre.required' => 'El nombre es obligatorio',
            'nombre.string' => 'El nombre debe ser una cadena de texto',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres',

            'precio.required' => 'El precio es obligatorio',
            'precio.numeric' => 'El precio debe ser un número',
            'precio.min' => 'El precio debe ser mayor a 0',
            'precio.max' => 'El precio no puede ser mayor a 999,999.99',

            'moneda.required' => 'La moneda es obligatoria',
            'moneda.boolean' => 'La moneda debe ser verdadero (soles) o falso (dólares)',

            'cuenta_debito_id.integer' => 'La cuenta débito debe ser un número entero',
            'cuenta_debito_id.exists' => 'La cuenta débito seleccionada no existe',

            'cuenta_credito_id.integer' => 'La cuenta crédito debe ser un número entero',
            'cuenta_credito_id.exists' => 'La cuenta crédito seleccionada no existe',
            'cuenta_credito_id.different' => 'La cuenta crédito debe ser diferente a la cuenta débito',

            'activo.boolean' => 'El estado activo debe ser verdadero o falso',

            'productos.array' => 'Los productos deben ser un arreglo',
            'productos.*.producto_id.required' => 'El producto es obligatorio',
            'productos.*.producto_id.exists' => 'El producto seleccionado no existe',
            'productos.*.cantidad.required' => 'La cantidad es obligatoria',
            'productos.*.cantidad.numeric' => 'La cantidad debe ser un número',
            'productos.*.cantidad.min' => 'La cantidad debe ser mayor a 0',

            // Mensajes para búsqueda
            'precio_min.numeric' => 'El precio mínimo debe ser un número',
            'precio_min.min' => 'El precio mínimo debe ser mayor o igual a 0',
            'precio_max.numeric' => 'El precio máximo debe ser un número',
            'precio_max.min' => 'El precio máximo debe ser mayor o igual a 0',
            'per_page.integer' => 'Los elementos por página deben ser un número entero',
            'per_page.min' => 'Debe mostrar al menos 1 elemento por página',
            'per_page.max' => 'No se pueden mostrar más de 100 elementos por página',

            // Mensajes para sincronización
            'uuids.required' => 'Los UUIDs son obligatorios',
            'uuids.array' => 'Los UUIDs deben ser un arreglo',
            'uuids.min' => 'Debe proporcionar al menos un UUID',
            'uuids.*.required' => 'Cada UUID es obligatorio',
            'uuids.*.string' => 'Cada UUID debe ser una cadena de texto',
            'uuids.*.uuid' => 'Cada UUID debe tener un formato válido'
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'nombre' => 'nombre',
            'precio' => 'precio',
            'moneda' => 'moneda',
            'activo' => 'estado activo',
            'precio_min' => 'precio mínimo',
            'precio_max' => 'precio máximo',
            'per_page' => 'elementos por página',
            'uuids' => 'UUIDs',
            'uuids.*' => 'UUID'
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
        // Convertir código a mayúsculas
        if ($this->has('codigo')) {
            $this->merge([
                'codigo' => strtoupper($this->codigo)
            ]);
        }

        // Limpiar y formatear nombre
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre)
            ]);
        }

        // Convertir precio a float
        if ($this->has('precio')) {
            $this->merge([
                'precio' => (float) $this->precio
            ]);
        }

        // Convertir valores booleanos
        if ($this->has('moneda')) {
            $this->merge([
                'moneda' => filter_var($this->moneda, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        if ($this->has('activo')) {
            $this->merge([
                'activo' => filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que precio_max sea mayor que precio_min
            if ($this->has('precio_min') && $this->has('precio_max')) {
                if ($this->precio_min > $this->precio_max) {
                    $validator->errors()->add('precio_max', 'El precio máximo debe ser mayor al precio mínimo');
                }
            }

            // Validaciones adicionales para creación/actualización
            if ($this->isMethod('POST') || $this->isMethod('PUT') || $this->isMethod('PATCH')) {
                // Validar formato del código
                if ($this->has('codigo')) {
                    $codigo = $this->codigo;
                    if (strlen($codigo) < 2) {
                        $validator->errors()->add('codigo', 'El código debe tener al menos 2 caracteres');
                    }
                }
            }
        });
    }
}
