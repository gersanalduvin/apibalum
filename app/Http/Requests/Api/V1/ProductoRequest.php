<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
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
        $productId = $this->route('id');

        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('inventario_producto', 'codigo')->ignore($productId)->whereNull('deleted_at')
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria_id' => 'required|integer|exists:inventario_categorias,id',
            'unidad_medida' => [
                'required',
                'string',
                Rule::in(['UND', 'KG', 'LT', 'M', 'M2', 'M3', 'PAR', 'DOC', 'CAJ', 'PAQ', 'BOL', 'FRA', 'GAL', 'TON', 'GR', 'ML', 'CM', 'MM', 'PZA', 'SET', 'ROL', 'TUB', 'LAT', 'SAC', 'BID', 'TAR', 'PLI', 'MIL', 'CTO', 'GLB'])
            ],
            'stock_minimo' => 'required|integer|min:0',
            'stock_maximo' => 'required|integer|min:1',
            'stock_actual' => 'required|integer|min:0',
            'costo_promedio' => 'required|numeric|min:0.01',
            'precio_venta' => 'required|numeric|min:0.01',
            'moneda' => 'required|boolean',
            'cuenta_inventario_id' => 'nullable|integer|exists:config_catalogo_cuentas,id',
            'cuenta_costo_id' => 'nullable|integer|exists:config_catalogo_cuentas,id',
            'cuenta_venta_id' => 'nullable|integer|exists:config_catalogo_cuentas,id',
            'activo' => 'boolean'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del producto es obligatorio',
            'codigo.unique' => 'Ya existe un producto con este código',
            'codigo.max' => 'El código no puede tener más de 50 caracteres',
            'nombre.required' => 'El nombre del producto es obligatorio',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres',
            'categoria_id.required' => 'La categoría es obligatoria',
            'categoria_id.required' => 'La categoría es obligatoria',
            'categoria_id.required' => 'La categoría es obligatoria',
            'categoria_id.exists' => 'La categoría seleccionada no existe',
            'unidad_medida.required' => 'La unidad de medida es obligatoria',
            'unidad_medida.in' => 'La unidad de medida seleccionada no es válida',
            'costo_promedio.required' => 'El costo promedio es obligatorio',
            'costo_promedio.numeric' => 'El costo promedio debe ser un número',
            'costo_promedio.min' => 'El costo promedio debe ser mayor a 0',
            'precio_venta.required' => 'El precio de venta es obligatorio',
            'precio_venta.numeric' => 'El precio de venta debe ser un número',
            'precio_venta.min' => 'El precio de venta debe ser mayor a 0',
            'stock_minimo.required' => 'El stock mínimo es obligatorio',
            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo',
            'stock_maximo.required' => 'El stock máximo es obligatorio',
            'stock_maximo.integer' => 'El stock máximo debe ser un número entero',
            'stock_maximo.min' => 'El stock máximo debe ser mayor a 0',
            'stock_actual.required' => 'El stock actual es obligatorio',
            'stock_actual.integer' => 'El stock actual debe ser un número entero',
            'stock_actual.min' => 'El stock actual no puede ser negativo',
            'moneda.required' => 'La moneda es obligatoria',
            'moneda.boolean' => 'La moneda debe ser un valor booleano (false=Córdoba, true=Dólar)',
            'cuenta_inventario_id.exists' => 'La cuenta de inventario seleccionada no existe',
            'cuenta_costo_id.exists' => 'La cuenta de costo seleccionada no existe',
            'cuenta_venta_id.exists' => 'La cuenta de venta seleccionada no existe',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'categoria_id' => 'categoría',
            'unidad_medida' => 'unidad de medida',
            'costo_promedio' => 'costo promedio',
            'precio_venta' => 'precio de venta',
            'stock_minimo' => 'stock mínimo',
            'stock_maximo' => 'stock máximo',
            'stock_actual' => 'stock actual',
            'moneda' => 'moneda',
            'cuenta_inventario_id' => 'cuenta de inventario',
            'cuenta_costo_id' => 'cuenta de costo',
            'cuenta_venta_id' => 'cuenta de venta',
            'activo' => 'activo'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'costo_promedio' => ($this->costo_promedio !== null && $this->costo_promedio !== '') ? (float) $this->costo_promedio : null,
            'precio_venta' => ($this->precio_venta !== null && $this->precio_venta !== '') ? (float) $this->precio_venta : null,
            'stock_minimo' => ($this->stock_minimo !== null && $this->stock_minimo !== '') ? (int) $this->stock_minimo : null,
            'stock_maximo' => ($this->stock_maximo !== null && $this->stock_maximo !== '') ? (int) $this->stock_maximo : null,
            'stock_actual' => ($this->stock_actual !== null && $this->stock_actual !== '') ? (int) $this->stock_actual : null,
            'cuenta_inventario_id' => $this->cuenta_inventario_id ? (int) $this->cuenta_inventario_id : null,
            'cuenta_costo_id' => $this->cuenta_costo_id ? (int) $this->cuenta_costo_id : null,
            'cuenta_venta_id' => $this->cuenta_venta_id ? (int) $this->cuenta_venta_id : null,
            'activo' => $this->activo !== null ? (bool) $this->activo : true,
            'moneda' => $this->moneda !== null ? (bool) $this->moneda : false,
        ]);
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validar que el stock máximo sea mayor al mínimo
            if ($this->stock_maximo && $this->stock_minimo && $this->stock_maximo <= $this->stock_minimo) {
                $validator->errors()->add('stock_maximo', 'El stock máximo debe ser mayor al stock mínimo');
            }

            // Validar que el stock actual esté dentro del rango
            if ($this->stock_actual && $this->stock_maximo && $this->stock_actual > $this->stock_maximo) {
                $validator->errors()->add('stock_actual', 'El stock actual no puede ser mayor al stock máximo');
            }

            // Validar que el precio de venta no sea menor al costo promedio
            $precio = $this->input('precio_venta');
            $costo = $this->input('costo_promedio');

            if (!is_null($precio) && !is_null($costo)) {
                if ((float)$precio < (float)$costo) {
                    $validator->errors()->add('precio_venta', 'El precio de venta no puede ser menor al costo promedio');
                }
            }
        });
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
}
