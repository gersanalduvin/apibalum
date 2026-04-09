<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Validation\Rule;

class ReciboRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_recibo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('recibos')->where(function ($query) {
                    return $query->where('tipo', $this->tipo)
                        ->whereNull('deleted_at');
                })
            ],
            'tipo' => 'required|in:interno,externo',
            'user_id' => 'nullable|integer|exists:users,id',
            'estado' => 'nullable|in:activo,anulado',
            'fecha' => 'nullable|date',
            'nombre_usuario' => 'required|string|max:255',
            'total' => 'nullable|numeric|min:0',
            'grado' => 'nullable|string|max:100',
            'seccion' => 'nullable|string|max:100',

            'detalles' => 'required|array|min:1',
            'detalles.*.rubro_id' => 'nullable|integer|exists:users_aranceles,id',
            'detalles.*.producto_id' => 'nullable|integer|exists:inventario_producto,id',
            'detalles.*.aranceles_id' => 'nullable|integer|exists:config_aranceles,id',
            'detalles.*.concepto' => 'nullable|string|max:255',
            'detalles.*.cantidad' => 'required|numeric|min:0',
            'detalles.*.monto' => 'nullable|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0',
            'detalles.*.total' => 'nullable|numeric|min:0',
            'detalles.*.tipo_pago' => 'nullable|in:parcial,total',

            'formas_pago' => 'required|array|min:1',
            'formas_pago.*.forma_pago_id' => 'required|integer|exists:config_formas_pago,id',
            'formas_pago.*.monto' => 'required|numeric|min:0',
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
