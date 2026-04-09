<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class GenerarBoletinRequest extends FormRequest
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
        return [
            'grupo_id' => 'required|integer|exists:config_grupos,id',
            'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
            'corte_id' => 'nullable', // Allows both integers (standard IDs) and strings (S1, S2, NF)
            'mostrar_escala' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'grupo_id.required' => 'El grupo es requerido',
            'grupo_id.integer' => 'El grupo debe ser un número entero',
            'grupo_id.exists' => 'El grupo seleccionado no existe',
            'periodo_lectivo_id.required' => 'El periodo lectivo es requerido',
            'periodo_lectivo_id.integer' => 'El periodo lectivo debe ser un número entero',
            'periodo_lectivo_id.exists' => 'El periodo lectivo seleccionado no existe',
        ];
    }
}
