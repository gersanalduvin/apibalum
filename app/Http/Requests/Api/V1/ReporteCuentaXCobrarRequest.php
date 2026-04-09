<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReporteCuentaXCobrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $grupo = $this->input('grupo_id');
        if (is_string($grupo) && strtolower($grupo) === 'todos') {
            $this->merge(['grupo_id' => null]);
        }

        $meses = $this->input('meses');
        if (is_array($meses)) {
            $meses = array_map(fn($m) => is_string($m) ? strtolower(trim($m)) : $m, $meses);
            $this->merge(['meses' => $meses]);
        }
    }

    public function rules(): array
    {
        return [
            'periodo_lectivo_id' => 'required|integer|exists:conf_periodo_lectivos,id',
            'turno_id' => 'sometimes|nullable|integer|exists:config_turnos,id',
            'grupo_id' => 'sometimes|nullable|integer|exists:config_grupos,id',
            'meses' => 'sometimes|array',
            'meses.*' => 'in:matricula,enero,febrero,marzo,abril,mayo,junio,julio,agosto,septiembre,octubre,noviembre,diciembre',
            'solo_pendientes' => 'sometimes|boolean'
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
