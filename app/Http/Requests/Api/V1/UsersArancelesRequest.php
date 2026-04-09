<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UsersArancelesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $method = $this->getMethod();
        $routeName = $this->route()->getName();

        // Reglas específicas según el endpoint
        switch ($routeName) {
            case 'users-aranceles.store':
                return $this->storeRules();

            case 'users-aranceles.anular-recargo':
                return $this->anularRecargoRules();

            case 'users-aranceles.exonerar':
                return $this->exonerarRules();

            case 'users-aranceles.aplicar-beca':
                return $this->aplicarBecaRules();

            case 'users-aranceles.aplicar-descuento':
                return $this->aplicarDescuentoRules();

            case 'users-aranceles.aplicar-plan-pago':
                return $this->aplicarPlanPagoRules();

            case 'users-aranceles.aplicar-pago':
                return $this->aplicarPagoRules();

            default:
                return $this->defaultRules();
        }
    }

    /**
     * Reglas para crear un nuevo arancel
     */
    private function storeRules(): array
    {
        return [
            'rubro_id' => 'nullable|integer|exists:config_plan_pago_detalle,id',
            'user_id' => 'required|integer|exists:users,id',
            'aranceles_id' => 'nullable|integer|exists:config_aranceles,id',
            'producto_id' => 'nullable|integer|exists:inventario_producto,id',
            'importe' => 'required|numeric|min:0|max:999999.99',
            'beca' => 'nullable|numeric|min:0|max:999999.99',
            'descuento' => 'nullable|numeric|min:0|max:999999.99',
            'recargo' => 'nullable|numeric|min:0|max:999999.99',
            'estado' => ['nullable', Rule::in(['pendiente', 'pagado', 'exonerado'])],
            'fecha_exonerado' => 'nullable|date',
            'observacion_exonerado' => 'nullable|string|max:1000',
            'fecha_recargo_anulado' => 'nullable|date',
            'recargo_anulado_por' => 'nullable|integer|exists:users,id',
            'observacion_recargo' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Reglas para anular recargos
     */
    private function anularRecargoRules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users_aranceles,id',
            'fecha_recargo_anulado' => 'nullable|date',
            'recargo_anulado_por' => 'nullable|integer|exists:users,id',
            'observacion_recargo' => 'required|string|max:1000|min:5'
        ];
    }

    /**
     * Reglas para aplicar becas
     */
    private function aplicarBecaRules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users_aranceles,id',
            'beca' => 'required|numeric|min:0'
        ];
    }

    /**
     * Reglas para aplicar descuentos
     */
    private function aplicarDescuentoRules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users_aranceles,id',
            'descuento' => 'required|numeric|min:0'
        ];
    }

    /**
     * Reglas para exonerar aranceles
     */
    private function exonerarRules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users_aranceles,id',
            'fecha_exonerado' => 'nullable|date',
            'observacion_exonerado' => 'required|string|max:1000|min:5'
        ];
    }

    /**
     * Reglas para aplicar plan de pago
     */
    private function aplicarPlanPagoRules(): array
    {
        return [
            'plan_pago_id' => 'required|integer|exists:config_plan_pago,id',
            'user_id' => 'required|integer|exists:users,id'
        ];
    }

    /**
     * Reglas para aplicar pago
     */
    private function aplicarPagoRules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:users_aranceles,id'
        ];
    }

    /**
     * Reglas por defecto
     */
    private function defaultRules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'estado' => ['nullable', Rule::in(['pendiente', 'pagado', 'exonerado'])],
            'rubro_id' => 'nullable|integer|exists:config_plan_pago_detalle,id',
            'con_recargo' => 'nullable|boolean',
            'con_saldo_pendiente' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100'
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Debe seleccionar al menos un arancel',
            'ids.array' => 'Los IDs deben ser un array',
            'ids.min' => 'Debe seleccionar al menos un arancel',
            'ids.*.required' => 'Cada ID es requerido',
            'ids.*.integer' => 'Cada ID debe ser un número entero',
            'ids.*.exists' => 'Uno o más aranceles seleccionados no existen',

            'user_id.required' => 'El usuario es requerido',
            'user_id.integer' => 'El ID del usuario debe ser un número entero',
            'user_id.exists' => 'El usuario seleccionado no existe',

            'plan_pago_id.required' => 'El plan de pago es requerido',
            'plan_pago_id.integer' => 'El ID del plan de pago debe ser un número entero',
            'plan_pago_id.exists' => 'El plan de pago seleccionado no existe',

            'rubro_id.integer' => 'El ID del rubro debe ser un número entero',
            'rubro_id.exists' => 'El rubro seleccionado no existe',

            'aranceles_id.integer' => 'El ID del arancel debe ser un número entero',
            'aranceles_id.exists' => 'El arancel seleccionado no existe',

            'producto_id.integer' => 'El ID del producto debe ser un número entero',
            'producto_id.exists' => 'El producto seleccionado no existe',

            'importe.required' => 'El importe es requerido',
            'importe.numeric' => 'El importe debe ser un número',
            'importe.min' => 'El importe debe ser mayor o igual a 0',
            'importe.max' => 'El importe no puede ser mayor a 999,999.99',

            'beca.numeric' => 'La beca debe ser un número',
            'beca.min' => 'La beca debe ser mayor o igual a 0',
            'beca.max' => 'La beca no puede ser mayor a 999,999.99',

            'descuento.numeric' => 'El descuento debe ser un número',
            'descuento.min' => 'El descuento debe ser mayor o igual a 0',
            'descuento.max' => 'El descuento no puede ser mayor a 999,999.99',

            'recargo.numeric' => 'El recargo debe ser un número',
            'recargo.min' => 'El recargo debe ser mayor o igual a 0',
            'recargo.max' => 'El recargo no puede ser mayor a 999,999.99',

            'estado.in' => 'El estado debe ser: pendiente, pagado o exonerado',

            'fecha_exonerado.date' => 'La fecha de exoneración debe ser una fecha válida',
            'fecha_recargo_anulado.date' => 'La fecha de anulación del recargo debe ser una fecha válida',

            'observacion_exonerado.required' => 'La observación de exoneración es requerida',
            'observacion_exonerado.string' => 'La observación de exoneración debe ser texto',
            'observacion_exonerado.max' => 'La observación de exoneración no puede exceder 1000 caracteres',
            'observacion_exonerado.min' => 'La observación de exoneración debe tener al menos 5 caracteres',

            'observacion_recargo.required' => 'La observación del recargo es requerida',
            'observacion_recargo.string' => 'La observación del recargo debe ser texto',
            'observacion_recargo.max' => 'La observación del recargo no puede exceder 1000 caracteres',
            'observacion_recargo.min' => 'La observación del recargo debe tener al menos 5 caracteres',

            'recargo_anulado_por.integer' => 'El ID del usuario que anula el recargo debe ser un número entero',
            'recargo_anulado_por.exists' => 'El usuario que anula el recargo no existe',

            'search.string' => 'El término de búsqueda debe ser texto',
            'search.max' => 'El término de búsqueda no puede exceder 255 caracteres',

            'con_recargo.boolean' => 'El filtro de recargo debe ser verdadero o falso',
            'con_saldo_pendiente.boolean' => 'El filtro de saldo pendiente debe ser verdadero o falso',

            'per_page.integer' => 'La cantidad por página debe ser un número entero',
            'per_page.min' => 'La cantidad por página debe ser al menos 1',
            'per_page.max' => 'La cantidad por página no puede ser mayor a 100'
        ];
    }

    /**
     * Atributos personalizados para los mensajes de error
     */
    public function attributes(): array
    {
        return [
            'ids' => 'aranceles',
            'ids.*' => 'arancel',
            'user_id' => 'usuario',
            'plan_pago_id' => 'plan de pago',
            'rubro_id' => 'rubro',
            'aranceles_id' => 'arancel',
            'producto_id' => 'producto',
            'importe' => 'importe',
            'beca' => 'beca',
            'descuento' => 'descuento',
            'recargo' => 'recargo',
            'estado' => 'estado',
            'fecha_exonerado' => 'fecha de exoneración',
            'observacion_exonerado' => 'observación de exoneración',
            'fecha_recargo_anulado' => 'fecha de anulación del recargo',
            'recargo_anulado_por' => 'usuario que anula el recargo',
            'observacion_recargo' => 'observación del recargo',
            'search' => 'búsqueda',
            'con_recargo' => 'con recargo',
            'con_saldo_pendiente' => 'con saldo pendiente',
            'per_page' => 'por página'
        ];
    }

    /**
     * Manejo de errores de validación
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
     * Validaciones adicionales después de las reglas básicas
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $routeName = $this->route()->getName();

            // Validaciones específicas para crear arancel
            if ($routeName === 'users-aranceles.store') {
                $this->validateStoreLogic($validator);
            }

            // Validaciones específicas para quitar recargos
            if ($routeName === 'users-aranceles.quitar-recargos') {
                $this->validateQuitarRecargosLogic($validator);
            }
        });
    }

    /**
     * Validaciones lógicas para crear arancel
     */
    private function validateStoreLogic(Validator $validator): void
    {
        $importe = $this->input('importe', 0);
        $beca = $this->input('beca', 0);
        $descuento = $this->input('descuento', 0);

        // Validar que beca + descuento no sea mayor al importe
        if (($beca + $descuento) > $importe) {
            $validator->errors()->add('beca', 'La suma de beca y descuento no puede ser mayor al importe');
            $validator->errors()->add('descuento', 'La suma de beca y descuento no puede ser mayor al importe');
        }

        // Validar que si se proporciona fecha_exonerado, el estado debe ser exonerado
        if ($this->filled('fecha_exonerado') && $this->input('estado') !== 'exonerado') {
            $validator->errors()->add('estado', 'Si se proporciona fecha de exoneración, el estado debe ser "exonerado"');
        }

        // Validar que si el estado es exonerado, se requiere observación
        if ($this->input('estado') === 'exonerado' && !$this->filled('observacion_exonerado')) {
            $validator->errors()->add('observacion_exonerado', 'La observación es requerida cuando el estado es "exonerado"');
        }
    }

    /**
     * Validaciones lógicas para quitar recargos
     */
    private function validateQuitarRecargosLogic(Validator $validator): void
    {
        // Aquí se pueden agregar validaciones adicionales específicas
        // Por ejemplo, verificar que los aranceles tengan recargos
        // Esto se puede hacer en el servicio para evitar consultas innecesarias
    }
}
