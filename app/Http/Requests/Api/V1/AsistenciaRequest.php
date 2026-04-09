<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Asistencia;

class AsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cortes = implode(',', Asistencia::CORTES);
        $estados = implode(',', Asistencia::ESTADOS);

        $rules = [
            'grupo_id' => 'required|integer|exists:config_grupos,id',
            'fecha' => 'required|date|before_or_equal:today',
            'corte' => 'required|in:' . $cortes,
            'excepciones' => 'present|array',
            'excepciones.*.user_id' => 'required|integer|exists:users,id',
            'excepciones.*.estado' => 'required|in:' . $estados,
            'excepciones.*.justificacion' => 'nullable|string',
            'excepciones.*.hora_registro' => 'nullable',
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules = [
                'estado' => 'nullable|in:' . $estados . ',presente',
                'justificacion' => 'nullable|string',
                'hora_registro' => 'nullable',
            ];
        }

        return $rules;
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
