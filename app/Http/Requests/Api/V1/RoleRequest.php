<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Services\PermissionService;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('id');

        // Obtener permisos válidos dinámicamente
        $permissionService = app(PermissionService::class);
        $allPermissions = $permissionService->getFlatPermissions();
        $validPermissions = array_column($allPermissions, 'permission');

        $rules = [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
                Rule::unique('roles', 'nombre')->ignore($roleId)->whereNull('deleted_at')
            ],
            'permisos' => 'nullable|array',
            'permisos.*' => [
                'string',
                Rule::in($validPermissions)
            ]
        ];

        // Para actualización, hacer el nombre opcional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['nombre'][0] = 'sometimes';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio.',
            'nombre.string' => 'El nombre del rol debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del rol no puede exceder los 255 caracteres.',
            'nombre.regex' => 'El nombre del rol solo puede contener letras y espacios.',
            'nombre.unique' => 'Ya existe un rol con este nombre.',
            'permisos.array' => 'Los permisos deben ser un arreglo.',
            'permisos.*.string' => 'Cada permiso debe ser una cadena de texto.',
            'permisos.*.in' => 'El permiso seleccionado no es válido.'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del rol',
            'permisos' => 'permisos',
            'permisos.*' => 'permiso'
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
        // Limpiar y normalizar el nombre
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre)
            ]);
        }

        // Asegurar que permisos sea un array y remover duplicados
        if ($this->has('permisos') && is_array($this->permisos)) {
            // Filtrar valores vacíos y remover duplicados de strings
            $permisos = array_filter($this->permisos, function ($permiso) {
                return !empty($permiso) && is_string($permiso);
            });

            $this->merge([
                'permisos' => array_values(array_unique($permisos))
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Sin validaciones personalizadas de permisos
        });
    }
}
