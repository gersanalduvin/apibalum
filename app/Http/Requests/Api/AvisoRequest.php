<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AvisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Control de acceso se hará en el controller via Service/Policy
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'links' => 'nullable|array', // Changed to accept an array of links
            'links.*.url' => 'required|url', // Each link object must have a 'url' property that is a valid URL
            'links.*.description' => 'nullable|string', // Each link object can optionally have a 'description' property
            'prioridad' => 'nullable|in:baja,normal,alta',
            'fecha_vencimiento' => 'nullable|date',
            'grupos' => 'nullable|array',
            'grupos.*' => 'exists:config_grupos,id',
            'para_todos' => 'nullable|boolean',
            'adjuntos' => 'nullable|array',
            'adjuntos.*' => 'file|max:10240', // Max 10MB por archivo
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'contenido.required' => 'El contenido es obligatorio.',
            'link.url' => 'El link debe ser una URL válida.',
            'adjuntos.*.max' => 'El archivo no debe pesar más de 10MB.',
        ];
    }
}
