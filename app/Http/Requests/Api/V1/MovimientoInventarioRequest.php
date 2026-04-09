<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MovimientoInventarioRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $movimientoId = $this->route('id');
        
        return [
            'producto_id' => [
                'required',
                'integer',
                'exists:inventario_producto,id'
            ],
            'almacen_id' => [
                'nullable',
                'integer'
            ],
            'tipo_movimiento' => [
                'required',
                'string',
                Rule::in(['entrada', 'salida', 'ajuste_positivo', 'ajuste_negativo', 'transferencia'])
            ],
            'cantidad' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99'
            ],
            'costo_unitario' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'documento_fecha' => [
                'nullable',
                'date',
                'before_or_equal:today'
            ],
            'documento_tipo' => [
                'nullable',
                'string',
                'max:50'
            ],
            'documento_numero' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventario_movimientos', 'documento_numero')
                    ->ignore($this->route('id'))
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'propiedades_adicionales' => [
                'nullable',
                'array'
            ],
            'propiedades_adicionales.lote' => [
                'nullable',
                'string',
                'max:50'
            ],
            'propiedades_adicionales.fecha_vencimiento' => [
                'nullable',
                'date',
                'after:today'
            ],
            'propiedades_adicionales.ubicacion' => [
                'nullable',
                'string',
                'max:100'
            ],
            'propiedades_adicionales.proveedor_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'propiedades_adicionales.cliente_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'propiedades_adicionales.orden_compra' => [
                'nullable',
                'string',
                'max:50'
            ],
            'propiedades_adicionales.orden_venta' => [
                'nullable',
                'string',
                'max:50'
            ],
            'propiedades_adicionales.motivo_ajuste' => [
                'nullable',
                'string',
                'max:255'
            ],
            'propiedades_adicionales.almacen_destino_id' => [
                'nullable',
                'integer'
            ],
            'propiedades_adicionales.temperatura' => [
                'nullable',
                'numeric',
                'between:-50,100'
            ],
            'propiedades_adicionales.humedad' => [
                'nullable',
                'numeric',
                'between:0,100'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio',
            'producto_id.exists' => 'El producto seleccionado no existe',
            'almacen_id.exists' => 'El almacén seleccionado no existe',
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser: entrada, salida, ajuste_positivo, ajuste_negativo o transferencia',
            'cantidad.required' => 'La cantidad es obligatoria',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
            'cantidad.max' => 'La cantidad no puede ser mayor a 999,999.99',
            'costo_unitario.min' => 'El costo unitario no puede ser negativo',
            'costo_unitario.max' => 'El costo unitario no puede ser mayor a 999,999.99',
            'documento_fecha.date' => 'La fecha del documento debe ser una fecha válida',
            'documento_fecha.before_or_equal' => 'La fecha del documento no puede ser futura',
            'documento_tipo.max' => 'El tipo de documento no puede tener más de 50 caracteres',
            'documento_numero.max' => 'El número de documento no puede tener más de 100 caracteres',
            'documento_numero.unique' => 'El número de documento ya existe',
            'observaciones.max' => 'Las observaciones no pueden tener más de 1000 caracteres',
            'propiedades_adicionales.lote.max' => 'El lote no puede tener más de 50 caracteres',
            'propiedades_adicionales.fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy',
            'propiedades_adicionales.ubicacion.max' => 'La ubicación no puede tener más de 100 caracteres',
            'propiedades_adicionales.proveedor_id.exists' => 'El usuario proveedor seleccionado no existe',
            'propiedades_adicionales.cliente_id.exists' => 'El usuario cliente seleccionado no existe',
            'propiedades_adicionales.orden_compra.max' => 'La orden de compra no puede tener más de 50 caracteres',
            'propiedades_adicionales.orden_venta.max' => 'La orden de venta no puede tener más de 50 caracteres',
            'propiedades_adicionales.motivo_ajuste.max' => 'El motivo del ajuste no puede tener más de 255 caracteres',
            'propiedades_adicionales.almacen_destino_id.exists' => 'El almacén destino seleccionado no existe',
            'propiedades_adicionales.temperatura.between' => 'La temperatura debe estar entre -50 y 100 grados',
            'propiedades_adicionales.humedad.between' => 'La humedad debe estar entre 0 y 100%'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'producto_id' => 'producto',
            'almacen_id' => 'almacén',
            'tipo_movimiento' => 'tipo de movimiento',
            'cantidad' => 'cantidad',
            'costo_unitario' => 'costo unitario',
            'documento_fecha' => 'fecha del documento',
            'documento_tipo' => 'tipo de documento',
            'documento_numero' => 'número de documento',
            'observaciones' => 'observaciones',
            'propiedades_adicionales.lote' => 'lote',
            'propiedades_adicionales.fecha_vencimiento' => 'fecha de vencimiento',
            'propiedades_adicionales.ubicacion' => 'ubicación',
            'propiedades_adicionales.proveedor_id' => 'usuario proveedor',
            'propiedades_adicionales.cliente_id' => 'usuario cliente',
            'propiedades_adicionales.orden_compra' => 'orden de compra',
            'propiedades_adicionales.orden_venta' => 'orden de venta',
            'propiedades_adicionales.motivo_ajuste' => 'motivo del ajuste',
            'propiedades_adicionales.almacen_destino_id' => 'almacén destino',
            'propiedades_adicionales.temperatura' => 'temperatura',
            'propiedades_adicionales.humedad' => 'humedad'
        ];
    }

    /**
     * Handle a failed validation attempt.
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
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        // Convertir cantidad a float
        if (isset($data['cantidad'])) {
            $data['cantidad'] = (float) $data['cantidad'];
        }

        // Convertir costo_unitario a float
        if (isset($data['costo_unitario'])) {
            $data['costo_unitario'] = (float) $data['costo_unitario'];
        }

        // Limpiar propiedades adicionales vacías
        if (isset($data['propiedades_adicionales']) && is_array($data['propiedades_adicionales'])) {
            $data['propiedades_adicionales'] = array_filter($data['propiedades_adicionales'], function($value) {
                return $value !== null && $value !== '';
            });
            
            if (empty($data['propiedades_adicionales'])) {
                $data['propiedades_adicionales'] = null;
            }
        }

        // Normalizar tipo_movimiento a minúsculas
        if (isset($data['tipo_movimiento'])) {
            $data['tipo_movimiento'] = strtolower(trim($data['tipo_movimiento']));
        }

        $this->replace($data);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $this->validated();

            // Validar que para transferencias se requiera almacén destino
            if ($data['tipo_movimiento'] === 'transferencia') {
                if (empty($data['propiedades_adicionales']['almacen_destino_id'])) {
                    $validator->errors()->add(
                        'propiedades_adicionales.almacen_destino_id',
                        'El almacén destino es obligatorio para transferencias'
                    );
                }
                
                // Validar que el almacén origen sea diferente al destino
                if (!empty($data['almacen_id']) && 
                    !empty($data['propiedades_adicionales']['almacen_destino_id']) &&
                    $data['almacen_id'] == $data['propiedades_adicionales']['almacen_destino_id']) {
                    $validator->errors()->add(
                        'propiedades_adicionales.almacen_destino_id',
                        'El almacén destino debe ser diferente al almacén origen'
                    );
                }
            }

            // Validar que para ajustes se requiera motivo
            if (in_array($data['tipo_movimiento'], ['ajuste_positivo', 'ajuste_negativo'])) {
                if (empty($data['propiedades_adicionales']['motivo_ajuste'])) {
                    $validator->errors()->add(
                        'propiedades_adicionales.motivo_ajuste',
                        'El motivo del ajuste es obligatorio'
                    );
                }
            }

            // Validar que para entradas se requiera costo unitario
            if ($data['tipo_movimiento'] === 'entrada') {
                if (empty($data['costo_unitario']) || $data['costo_unitario'] <= 0) {
                    $validator->errors()->add(
                        'costo_unitario',
                        'El costo unitario es obligatorio para entradas'
                    );
                }
            }

            // Validar fecha de vencimiento solo para productos que lo requieran
            if (!empty($data['propiedades_adicionales']['fecha_vencimiento'])) {
                $fechaVencimiento = \Carbon\Carbon::parse($data['propiedades_adicionales']['fecha_vencimiento']);
                $fechaMovimiento = \Carbon\Carbon::parse($data['documento_fecha'] ?? now());
                
                if ($fechaVencimiento->lt($fechaMovimiento)) {
                    $validator->errors()->add(
                        'propiedades_adicionales.fecha_vencimiento',
                        'La fecha de vencimiento no puede ser anterior a la fecha del movimiento'
                    );
                }
            }
        });
    }
}