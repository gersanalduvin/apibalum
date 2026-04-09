<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $path = $this->path();
        if (str_contains($path, 'api/v1/usuarios/familias') || str_contains($path, 'usuarios/familias')) {
            $this->merge(['tipo_usuario' => 'familia']);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $tipoUsuario = $this->input('tipo_usuario');
        $fechaNacimientoRule = ($tipoUsuario === 'alumno') ? 'required|date|before:today' : 'nullable|date|before:today';

        $primerNombreRule = 'required|string|max:100';
        $primerApellidoRule = 'required|string|max:100';
        $sexoRule = 'required|string|in:M,F';

        if ($tipoUsuario === 'familia') {
            $primerApellidoRule = 'nullable|string|max:100';
            $sexoRule = 'nullable|string|in:M,F';
        }

        return [
            // Campos básicos del usuario
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => $isUpdate ? 'sometimes|string|min:8' : 'sometimes|string|min:8',
            'superadmin' => 'nullable|boolean',
            'role_id' => 'nullable|exists:roles,id',
            'tipo_usuario' => 'nullable|string|in:administrativo,superuser,alumno,docente,familia',

            // Campos requeridos para estudiantes
            'primer_nombre' => $primerNombreRule,
            'segundo_nombre' => 'nullable|string|max:100',
            'primer_apellido' => $primerApellidoRule,
            'segundo_apellido' => 'nullable|string|max:100',

            // Datos del usuario
            'fecha_nacimiento' => $fechaNacimientoRule,
            'lugar_nacimiento' => 'nullable|string|max:255',
            'sexo' => $sexoRule,
            'codigo_estudiantil' => 'nullable|string|max:50',
            'nivel' => 'nullable|string|max:100',
            'codigo_mined' => 'nullable|string|max:50',
            'codigo_unico' => 'nullable|string|max:50',
            'correo_notificaciones' => 'nullable|email|max:255',
            'foto' => 'nullable|string|max:500',
            'foto_file' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB máximo
            'trajo_epicrisis' => 'nullable|boolean',
            'nombre_persona_firma' => 'nullable|string|max:255',

            // Datos de la madre
            'nombre_madre' => 'nullable|string|max:255',
            'fecha_nacimiento_madre' => 'nullable|date|before:today',
            'cedula_madre' => 'nullable|string|max:20',
            'religion_madre' => 'nullable|string|max:100',
            'estado_civil_madre' => 'nullable|string|in:soltera,casada,divorciada,viuda,union_libre,separada,otro',
            'telefono_madre' => 'nullable|string|max:20',
            'telefono_claro_madre' => 'nullable|string|max:20',
            'telefono_tigo_madre' => 'nullable|string|max:20',
            'direccion_madre' => 'nullable|string|max:500',
            'barrio_madre' => 'nullable|string|max:255',
            'ocupacion_madre' => 'nullable|string|max:255',
            'lugar_trabajo_madre' => 'nullable|string|max:255',
            'telefono_trabajo_madre' => 'nullable|string|max:20',

            // Datos del padre
            'nombre_padre' => 'nullable|string|max:255',
            'fecha_nacimiento_padre' => 'nullable|date|before:today',
            'cedula_padre' => 'nullable|string|max:20',
            'religion_padre' => 'nullable|string|max:100',
            'estado_civil_padre' => 'nullable|string|in:soltero,casado,divorciado,viudo,union_libre,separado,otro',
            'telefono_padre' => 'nullable|string|max:20',
            'telefono_claro_padre' => 'nullable|string|max:20',
            'telefono_tigo_padre' => 'nullable|string|max:20',
            'direccion_padre' => 'nullable|string|max:500',
            'barrio_padre' => 'nullable|string|max:255',
            'ocupacion_padre' => 'nullable|string|max:255',
            'lugar_trabajo_padre' => 'nullable|string|max:255',
            'telefono_trabajo_padre' => 'nullable|string|max:20',

            // Responsable
            'nombre_responsable' => 'nullable|string|max:255',
            'cedula_responsable' => 'nullable|string|max:20',
            'telefono_responsable' => 'nullable|string|max:20',
            'direccion_responsable' => 'nullable|string|max:500',

            // Datos familiares
            'cantidad_hijos' => 'nullable|integer|min:0',
            'lugar_en_familia' => 'nullable|string|max:100',
            'personas_hogar' => 'nullable|string|max:1000',
            'encargado_alumno' => 'nullable|string|max:255',
            'contacto_emergencia' => 'nullable|string|max:255',
            'telefono_emergencia' => 'nullable|string|max:20',
            'metodos_disciplina' => 'nullable|string|max:1000',
            'pasatiempos_familiares' => 'nullable|string|max:1000',

            // Área médica / psicológica / social
            'personalidad' => 'nullable|string|max:1000',
            'parto' => 'nullable|string|in:natural,cesarea',
            'sufrimiento_fetal' => 'nullable|boolean',
            'edad_gateo' => 'nullable|integer|min:0',
            'edad_caminar' => 'nullable|integer|min:0',
            'edad_hablar' => 'nullable|integer|min:0',
            'habilidades' => 'nullable|string|max:1000',
            'pasatiempos' => 'nullable|string|max:1000',
            'preocupaciones' => 'nullable|string|max:1000',
            'juegos_preferidos' => 'nullable|string|max:1000',

            // Área social
            'se_relaciona_familiares' => 'nullable|boolean',
            'establece_relacion_coetaneos' => 'nullable|boolean',
            'evita_contacto_personas' => 'nullable|boolean',
            'especifique_evita_personas' => 'nullable|string|max:1000',
            'evita_lugares_situaciones' => 'nullable|boolean',
            'especifique_evita_lugares' => 'nullable|string|max:1000',
            'respeta_figuras_autoridad' => 'nullable|boolean',

            // Área comunicativa
            'atiende_cuando_llaman' => 'nullable|boolean',
            'es_capaz_comunicarse' => 'nullable|boolean',
            'comunica_palabras' => 'nullable|boolean',
            'comunica_señas' => 'nullable|boolean',
            'comunica_llanto' => 'nullable|boolean',
            'dificultad_expresarse' => 'nullable|boolean',
            'especifique_dificultad_expresarse' => 'nullable|string|max:1000',
            'dificultad_comprender' => 'nullable|boolean',
            'especifique_dificultad_comprender' => 'nullable|string|max:1000',
            'atiende_orientaciones' => 'nullable|boolean',

            // Área psicológica
            'estado_animo_general' => 'nullable|string|in:alegre,triste,enojado,indiferente',
            'tiene_fobias' => 'nullable|boolean',
            'generador_fobia' => 'nullable|string|max:1000',
            'tiene_agresividad' => 'nullable|boolean',
            'tipo_agresividad' => 'nullable|string|in:encubierta,directa',

            // Área médica detallada
            'patologias_detalle' => 'nullable|string|max:2000',
            'consume_farmacos' => 'nullable|boolean',
            'farmacos_detalle' => 'nullable|string|max:1000',
            'tiene_alergias' => 'nullable|boolean',
            'causas_alergia' => 'nullable|string|max:1000',
            'alteraciones_patron_sueño' => 'nullable|boolean',
            'se_duerme_temprano' => 'nullable|boolean',
            'se_duerme_tarde' => 'nullable|boolean',
            'apnea_sueño' => 'nullable|boolean',
            'pesadillas' => 'nullable|boolean',
            'enuresis_secundaria' => 'nullable|boolean',
            'alteraciones_apetito_detalle' => 'nullable|boolean',
            'aversion_alimentos' => 'nullable|string|max:1000',
            'reflujo' => 'nullable|boolean',
            'alimentos_favoritos' => 'nullable|string|max:1000',
            'alteracion_vision' => 'nullable|boolean',
            'alteracion_audicion' => 'nullable|boolean',
            'alteracion_tacto' => 'nullable|boolean',
            'especifique_alteraciones_sentidos' => 'nullable|string|max:1000',

            // Alteraciones físicas adicionales
            'alteraciones_oseas' => 'nullable|boolean',
            'alteraciones_musculares' => 'nullable|boolean',
            'pie_plano' => 'nullable|boolean',

            // Datos especiales
            'diagnostico_medico' => 'nullable|string|max:2000',
            'referido_escuela_especial' => 'nullable|boolean',
            'trajo_epicrisis' => 'nullable|boolean',
            'presenta_diagnostico_matricula' => 'nullable|boolean',

            // Campos adicionales de foto
            'foto_url' => 'nullable|string|max:500',
            'foto_path' => 'nullable|string|max:500',
            'foto_uploaded_at' => 'nullable|date',

            // Retiro
            'fecha_retiro' => 'nullable|date',
            'retiro_notificado' => 'nullable|boolean',
            'motivo_retiro' => 'nullable|string|max:1000',
            'informacion_retiro_adicional' => 'nullable|string|max:1000',

            // Observaciones y firma
            'observaciones' => 'nullable|string|max:2000',
            'nombre_persona_firma' => 'nullable|string|max:255',
            'cedula_firma' => 'nullable|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Mensajes básicos
            'email.email' => 'El email debe tener un formato válido',
            'email.unique' => 'Este email ya está registrado',
            'email.max' => 'El email no puede tener más de 255 caracteres',
            'password.string' => 'La contraseña debe ser una cadena de texto',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'superadmin.boolean' => 'El campo superadmin debe ser verdadero o falso',
            'role_id.exists' => 'El rol seleccionado no existe',

            // Campos requeridos
            'primer_nombre.required' => 'El primer nombre es obligatorio',
            'primer_nombre.string' => 'El primer nombre debe ser texto',
            'primer_nombre.max' => 'El primer nombre no puede tener más de 100 caracteres',
            'segundo_nombre.string' => 'El segundo nombre debe ser texto',
            'segundo_nombre.max' => 'El segundo nombre no puede tener más de 100 caracteres',
            'primer_apellido.required' => 'El primer apellido es obligatorio',
            'primer_apellido.string' => 'El primer apellido debe ser texto',
            'primer_apellido.max' => 'El primer apellido no puede tener más de 100 caracteres',
            'segundo_apellido.string' => 'El segundo apellido debe ser texto',
            'segundo_apellido.max' => 'El segundo apellido no puede tener más de 100 caracteres',

            // Validaciones de tipo
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'sexo.required' => 'El sexo es obligatorio',
            'sexo.in' => 'El sexo debe ser M (Masculino) o F (Femenino)',

            // Validaciones de edad de padres
            'edad_madre.integer' => 'La edad de la madre debe ser un número entero',
            'edad_madre.min' => 'La edad de la madre no puede ser menor a 0',
            'edad_madre.max' => 'La edad de la madre no puede ser mayor a 120',
            'edad_padre.integer' => 'La edad del padre debe ser un número entero',
            'edad_padre.min' => 'La edad del padre no puede ser menor a 0',
            'edad_padre.max' => 'La edad del padre no puede ser mayor a 120',

            // Validaciones de números
            'numero_hermanos.integer' => 'El número de hermanos debe ser un número entero',
            'numero_hermanos.min' => 'El número de hermanos no puede ser menor a 0',
            'peso_nacer.numeric' => 'El peso al nacer debe ser un número',
            'peso_nacer.min' => 'El peso al nacer no puede ser menor a 0',
            'talla_nacer.numeric' => 'La talla al nacer debe ser un número',
            'talla_nacer.min' => 'La talla al nacer no puede ser menor a 0',

            // Validaciones de fechas
            'fecha_inscripcion.date' => 'La fecha de inscripción debe ser una fecha válida',
            'fecha_retiro.date' => 'La fecha de retiro debe ser una fecha válida',

            // Validaciones de email
            'correo_notificaciones.email' => 'El correo de notificaciones debe tener un formato válido',

            // Validaciones de foto
            'foto_file.file' => 'El archivo de foto debe ser un archivo válido',
            'foto_file.image' => 'El archivo debe ser una imagen',
            'foto_file.mimes' => 'La foto debe ser de tipo: JPEG, JPG, PNG o WEBP',
            'foto_file.max' => 'La foto no puede ser mayor a 5MB',

            // Validaciones booleanas
            'embarazo_deseado.boolean' => 'El campo embarazo deseado debe ser verdadero o falso',
            'embarazo_controlado.boolean' => 'El campo embarazo controlado debe ser verdadero o falso',
            'parto_normal.boolean' => 'El campo parto normal debe ser verdadero o falso',
            'lloro_nacer.boolean' => 'El campo lloró al nacer debe ser verdadero o falso',
            'lactancia_materna.boolean' => 'El campo lactancia materna debe ser verdadero o falso',
            'desarrollo_normal.boolean' => 'El campo desarrollo normal debe ser verdadero o falso',
            'comunicacion_verbal.boolean' => 'El campo comunicación verbal debe ser verdadero o falso',
            'comunicacion_gestual.boolean' => 'El campo comunicación gestual debe ser verdadero o falso',
            'comunicacion_escrita.boolean' => 'El campo comunicación escrita debe ser verdadero o falso',
            'comprende_ordenes.boolean' => 'El campo comprende órdenes debe ser verdadero o falso',
            'sigue_instrucciones.boolean' => 'El campo sigue instrucciones debe ser verdadero o falso',
            'vocabulario_acorde.boolean' => 'El campo vocabulario acorde debe ser verdadero o falso',
            'pie_plano.boolean' => 'El campo pie plano debe ser verdadero o falso',
            'capacidad_diferente.boolean' => 'El campo capacidad diferente debe ser verdadero o falso',
            'referido_escuela_especial.boolean' => 'El campo referido a escuela especial debe ser verdadero o falso',
            'presenta_diagnostico_matricula.boolean' => 'El campo presenta diagnóstico al matricularse debe ser verdadero o falso',
            'requiere_asistente.boolean' => 'El campo requiere asistente debe ser verdadero o falso',
            'retiro_notificado.boolean' => 'El campo retiro notificado debe ser verdadero o falso',
            'trajo_epicrisis.boolean' => 'El campo trajo epicrisis debe ser verdadero o falso',
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
}
