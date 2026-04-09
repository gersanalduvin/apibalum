<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
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
        $categoriaId = $this->route('id');
        
        $rules = [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('inventario_categorias', 'codigo')->ignore($categoriaId)
            ],
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria_padre_id' => [
                'nullable',
                'integer',
                'exists:inventario_categorias,id',
                function ($attribute, $value, $fail) use ($categoriaId) {
                    // No puede ser padre de sí misma
                    if ($value == $categoriaId) {
                        $fail('Una categoría no puede ser padre de sí misma.');
                    }
                }
            ],
            'activo' => 'boolean',
            
        ];

        // Reglas específicas para actualización
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // Hacer algunos campos opcionales en actualización
            $rules['codigo'][0] = 'sometimes';
            $rules['nombre'] = 'sometimes|string|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El código de la categoría es obligatorio.',
            'codigo.unique' => 'Ya existe una categoría con este código.',
            'codigo.max' => 'El código no puede tener más de 50 caracteres.',
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'categoria_padre_id.exists' => 'La categoría padre seleccionada no existe.',
            'categoria_padre_id.integer' => 'El ID de la categoría padre debe ser un número entero.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'categoria_padre_id' => 'categoría padre',
            'activo' => 'activo',
            
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
        // Convertir strings a boolean si es necesario
        if ($this->has('activo')) {
            $this->merge([
                'activo' => filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
            ]);
        }

        // Asegurar que orden sea un entero
        // Campo 'orden' eliminado del esquema

        // Limpiar categoria_padre_id si está vacío
        if ($this->has('categoria_padre_id') && empty($this->categoria_padre_id)) {
            $this->merge([
                'categoria_padre_id' => null
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validación adicional para evitar ciclos en la jerarquía
            if ($this->has('categoria_padre_id') && $this->categoria_padre_id) {
                $categoriaId = $this->route('id');
                if ($categoriaId && $this->wouldCreateCycle($categoriaId, $this->categoria_padre_id)) {
                    $validator->errors()->add('categoria_padre_id', 'Esta asignación crearía un ciclo en la jerarquía de categorías.');
                }
            }
            // Campos de propiedades_adicionales eliminados
        });
    }

    /**
     * Verificar si una asignación de padre crearía un ciclo
     */
    private function wouldCreateCycle(int $categoriaId, int $nuevoPadreId): bool
    {
        $categoria = \App\Models\Categoria::find($nuevoPadreId);
        
        while ($categoria && $categoria->categoria_padre_id) {
            if ($categoria->categoria_padre_id == $categoriaId) {
                return true;
            }
            $categoria = \App\Models\Categoria::find($categoria->categoria_padre_id);
        }
        
        return false;
    }
}