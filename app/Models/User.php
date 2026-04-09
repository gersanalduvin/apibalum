<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, Auditable;

    // ======= CONSTANTES PARA CAMPOS ENUM =======

    // Constantes para sexo
    const SEXO_MASCULINO = 'M';
    const SEXO_FEMENINO = 'F';

    // Constantes para tipo_usuario
    const TIPO_ADMINISTRATIVO = 'administrativo';
    const TIPO_SUPERUSER = 'superuser';
    const TIPO_ALUMNO = 'alumno';
    const TIPO_DOCENTE = 'docente';
    const TIPO_FAMILIA = 'familia';

    // Constantes para estado_civil_madre
    const ESTADO_CIVIL_MADRE_SOLTERA = 'soltera';
    const ESTADO_CIVIL_MADRE_CASADA = 'casada';
    const ESTADO_CIVIL_MADRE_DIVORCIADA = 'divorciada';
    const ESTADO_CIVIL_MADRE_VIUDA = 'viuda';
    const ESTADO_CIVIL_MADRE_UNION_LIBRE = 'union_libre';
    const ESTADO_CIVIL_MADRE_SEPARADA = 'separada';
    const ESTADO_CIVIL_MADRE_OTRO = 'otro';

    // Constantes para estado_civil_padre
    const ESTADO_CIVIL_PADRE_SOLTERO = 'soltero';
    const ESTADO_CIVIL_PADRE_CASADO = 'casado';
    const ESTADO_CIVIL_PADRE_DIVORCIADO = 'divorciado';
    const ESTADO_CIVIL_PADRE_VIUDO = 'viudo';
    const ESTADO_CIVIL_PADRE_UNION_LIBRE = 'union_libre';
    const ESTADO_CIVIL_PADRE_SEPARADO = 'separado';
    const ESTADO_CIVIL_PADRE_OTRO = 'otro';

    // Constantes para parto
    const PARTO_NATURAL = 'natural';
    const PARTO_CESAREA = 'cesarea';

    // Constantes para estado_animo_general
    const ESTADO_ANIMO_ALEGRE = 'alegre';
    const ESTADO_ANIMO_TRISTE = 'triste';
    const ESTADO_ANIMO_ENOJADO = 'enojado';
    const ESTADO_ANIMO_INDIFERENTE = 'indiferente';

    // Constantes para tipo_agresividad
    const TIPO_AGRESIVIDAD_ENCUBIERTA = 'encubierta';
    const TIPO_AGRESIVIDAD_DIRECTA = 'directa';

    // ======= MÉTODOS ESTÁTICOS PARA OBTENER OPCIONES ENUM =======

    /**
     * Obtener opciones disponibles para el campo sexo
     */
    public static function getSexoOptions(): array
    {
        return [
            self::SEXO_MASCULINO => 'Masculino',
            self::SEXO_FEMENINO => 'Femenino'
        ];
    }

    /**
     * Obtener opciones disponibles para el campo tipo_usuario
     */
    public static function getTipoUsuarioOptions(): array
    {
        return [
            self::TIPO_ADMINISTRATIVO => 'Administrativo',
            self::TIPO_SUPERUSER => 'Super Usuario',
            self::TIPO_ALUMNO => 'Alumno',
            self::TIPO_DOCENTE => 'Docente',
            self::TIPO_FAMILIA => 'Familia'
        ];
    }

    /**
     * Obtener opciones disponibles para el campo estado_civil_madre
     */
    public static function getEstadoCivilMadreOptions(): array
    {
        return [
            self::ESTADO_CIVIL_MADRE_SOLTERA => 'Soltera',
            self::ESTADO_CIVIL_MADRE_CASADA => 'Casada',
            self::ESTADO_CIVIL_MADRE_DIVORCIADA => 'Divorciada',
            self::ESTADO_CIVIL_MADRE_VIUDA => 'Viuda',
            self::ESTADO_CIVIL_MADRE_UNION_LIBRE => 'Unión Libre',
            self::ESTADO_CIVIL_MADRE_SEPARADA => 'Separada',
            self::ESTADO_CIVIL_MADRE_OTRO => 'Otro'
        ];
    }

    /**
     * Obtener opciones disponibles para el campo estado_civil_padre
     */
    public static function getEstadoCivilPadreOptions(): array
    {
        return [
            self::ESTADO_CIVIL_PADRE_SOLTERO => 'Soltero',
            self::ESTADO_CIVIL_PADRE_CASADO => 'Casado',
            self::ESTADO_CIVIL_PADRE_DIVORCIADO => 'Divorciado',
            self::ESTADO_CIVIL_PADRE_VIUDO => 'Viudo',
            self::ESTADO_CIVIL_PADRE_UNION_LIBRE => 'Unión Libre',
            self::ESTADO_CIVIL_PADRE_SEPARADO => 'Separado',
            self::ESTADO_CIVIL_PADRE_OTRO => 'Otro'
        ];
    }

    /**
     * Obtener opciones disponibles para el campo parto
     */
    public static function getPartoOptions(): array
    {
        return [
            self::PARTO_NATURAL => 'Parto Natural',
            self::PARTO_CESAREA => 'Cesárea'
        ];
    }

    /**
     * Obtener opciones disponibles para el campo estado_animo_general
     */
    public static function getEstadoAnimoGeneralOptions(): array
    {
        return [
            self::ESTADO_ANIMO_ALEGRE => 'Alegre',
            self::ESTADO_ANIMO_TRISTE => 'Triste',
            self::ESTADO_ANIMO_ENOJADO => 'Enojado',
            self::ESTADO_ANIMO_INDIFERENTE => 'Indiferente'
        ];
    }

    /**
     * Obtener opciones disponibles para el campo tipo_agresividad
     */
    public static function getTipoAgresividadOptions(): array
    {
        return [
            self::TIPO_AGRESIVIDAD_ENCUBIERTA => 'Encubierta',
            self::TIPO_AGRESIVIDAD_DIRECTA => 'Directa'
        ];
    }

    // ======= ATRIBUTOS PERSONALIZADOS PARA CAMPOS ENUM =======

    /**
     * Atributo personalizado para sexo - devuelve el texto legible
     */
    public function getSexoTextAttribute(): ?string
    {
        if (!$this->sexo) {
            return null;
        }

        $options = self::getSexoOptions();
        return $options[$this->sexo] ?? $this->sexo;
    }

    /**
     * Atributo personalizado para tipo_usuario - devuelve el texto legible
     */
    public function getTipoUsuarioTextAttribute(): ?string
    {
        if (!$this->tipo_usuario) {
            return null;
        }

        $options = self::getTipoUsuarioOptions();
        return $options[$this->tipo_usuario] ?? $this->tipo_usuario;
    }

    /**
     * Atributo personalizado para estado_civil_madre - devuelve el texto legible
     */
    public function getEstadoCivilMadreTextAttribute(): ?string
    {
        if (!$this->estado_civil_madre) {
            return null;
        }

        $options = self::getEstadoCivilMadreOptions();
        return $options[$this->estado_civil_madre] ?? $this->estado_civil_madre;
    }

    /**
     * Atributo personalizado para estado_civil_padre - devuelve el texto legible
     */
    public function getEstadoCivilPadreTextAttribute(): ?string
    {
        if (!$this->estado_civil_padre) {
            return null;
        }

        $options = self::getEstadoCivilPadreOptions();
        return $options[$this->estado_civil_padre] ?? $this->estado_civil_padre;
    }

    /**
     * Atributo personalizado para parto - devuelve el texto legible
     */
    public function getPartoTextAttribute(): ?string
    {
        if (!$this->parto) {
            return null;
        }

        $options = self::getPartoOptions();
        return $options[$this->parto] ?? $this->parto;
    }

    /**
     * Atributo personalizado para estado_animo_general - devuelve el texto legible
     */
    public function getEstadoAnimoGeneralTextAttribute(): ?string
    {
        if (!$this->estado_animo_general) {
            return null;
        }

        $options = self::getEstadoAnimoGeneralOptions();
        return $options[$this->estado_animo_general] ?? $this->estado_animo_general;
    }

    /**
     * Atributo personalizado para tipo_agresividad - devuelve el texto legible
     */
    public function getTipoAgresividadTextAttribute(): ?string
    {
        if (!$this->tipo_agresividad) {
            return null;
        }

        $options = self::getTipoAgresividadOptions();
        return $options[$this->tipo_agresividad] ?? $this->tipo_agresividad;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'superadmin',
        'role_id',
        'created_by',
        'updated_by',
        'deleted_by',

        // ======= Datos del estudiante =======
        'codigo_mined',
        'codigo_unico',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'sexo',
        'foto',
        'foto_url',
        'foto_path',
        'foto_uploaded_at',
        'correo_notificaciones',
        'tipo_usuario',
        'activo',

        // ======= Datos de la madre =======
        'nombre_madre',
        'fecha_nacimiento_madre',
        'cedula_madre',
        'religion_madre',
        'estado_civil_madre',
        'telefono_madre',
        'telefono_claro_madre',
        'telefono_tigo_madre',
        'direccion_madre',
        'barrio_madre',
        'ocupacion_madre',
        'lugar_trabajo_madre',
        'telefono_trabajo_madre',

        // ======= Datos del padre =======
        'nombre_padre',
        'fecha_nacimiento_padre',
        'cedula_padre',
        'religion_padre',
        'estado_civil_padre',
        'telefono_padre',
        'telefono_claro_padre',
        'telefono_tigo_padre',
        'direccion_padre',
        'barrio_padre',
        'ocupacion_padre',
        'lugar_trabajo_padre',
        'telefono_trabajo_padre',

        // ======= Responsable =======
        'nombre_responsable',
        'cedula_responsable',
        'telefono_responsable',
        'direccion_responsable',

        // ======= Datos familiares =======
        'cantidad_hijos',
        'lugar_en_familia',
        'personas_hogar',
        'encargado_alumno',
        'contacto_emergencia',
        'telefono_emergencia',
        'metodos_disciplina',
        'pasatiempos_familiares',

        // ======= Área médica / psicológica / social =======
        'personalidad',
        'parto',
        'sufrimiento_fetal',
        'edad_gateo',
        'edad_caminar',
        'edad_hablar',
        'habilidades',
        'pasatiempos',
        'preocupaciones',
        'juegos_preferidos',

        // ======= Área social =======
        'se_relaciona_familiares',
        'establece_relacion_coetaneos',
        'evita_contacto_personas',
        'especifique_evita_personas',
        'evita_lugares_situaciones',
        'especifique_evita_lugares',
        'respeta_figuras_autoridad',

        // ======= Área comunicativa =======
        'atiende_cuando_llaman',
        'es_capaz_comunicarse',
        'comunica_palabras',
        'comunica_señas',
        'comunica_llanto',
        'dificultad_expresarse',
        'especifique_dificultad_expresarse',
        'dificultad_comprender',
        'especifique_dificultad_comprender',
        'atiende_orientaciones',

        // ======= Área psicológica =======
        'estado_animo_general',
        'tiene_fobias',
        'generador_fobia',
        'tiene_agresividad',
        'tipo_agresividad',

        // ======= Área médica detallada =======
        'patologias_detalle',
        'consume_farmacos',
        'farmacos_detalle',
        'tiene_alergias',
        'causas_alergia',
        'alteraciones_patron_sueño',
        'se_duerme_temprano',
        'se_duerme_tarde',
        'apnea_sueño',
        'pesadillas',
        'enuresis_secundaria',
        'alteraciones_apetito_detalle',
        'aversion_alimentos',
        'reflujo',
        'alimentos_favoritos',
        'alteracion_vision',
        'alteracion_audicion',
        'alteracion_tacto',
        'especifique_alteraciones_sentidos',

        // ======= Alteraciones físicas adicionales =======
        'alteraciones_oseas',
        'alteraciones_musculares',
        'pie_plano',

        // ======= Datos especiales =======
        'diagnostico_medico',
        'referido_escuela_especial',
        'trajo_epicrisis',
        'presenta_diagnostico_matricula',

        // ======= Retiro =======
        'fecha_retiro',
        'retiro_notificado',
        'motivo_retiro',
        'informacion_retiro_adicional',

        // ======= Observaciones =======
        'observaciones',

        // ======= Firma =======
        'nombre_persona_firma',
        'cedula_firma'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $appends = [
        'nombre_completo',
        'name',
        'edad',
        'edad_madre',
        'edad_padre',
        'fecha_nacimiento_espanol',
        'fecha_nacimiento_madre_espanol',
        'fecha_nacimiento_padre_espanol',
        'sexo_text',
        'tipo_usuario_text',
        'estado_civil_madre_text',
        'estado_civil_padre_text',
        'parto_text',
        'estado_animo_general_text',
        'tipo_agresividad_text',
        'fecha_retiro_espanol'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'superadmin' => 'boolean',
            'activo' => 'boolean',

            // Campos de fecha
            'fecha_nacimiento' => 'date',
            'fecha_nacimiento_madre' => 'date',
            'fecha_nacimiento_padre' => 'date',
            'fecha_retiro' => 'date',
            'foto_uploaded_at' => 'datetime',

            // Campos booleanos
            'sufrimiento_fetal' => 'boolean',
            'se_relaciona_familiares' => 'boolean',
            'establece_relacion_coetaneos' => 'boolean',
            'evita_contacto_personas' => 'boolean',
            'evita_lugares_situaciones' => 'boolean',
            'respeta_figuras_autoridad' => 'boolean',
            'atiende_cuando_llaman' => 'boolean',
            'es_capaz_comunicarse' => 'boolean',
            'comunica_palabras' => 'boolean',
            'comunica_señas' => 'boolean',
            'comunica_llanto' => 'boolean',
            'dificultad_expresarse' => 'boolean',
            'dificultad_comprender' => 'boolean',
            'atiende_orientaciones' => 'boolean',
            'tiene_fobias' => 'boolean',
            'tiene_agresividad' => 'boolean',
            'consume_farmacos' => 'boolean',
            'tiene_alergias' => 'boolean',
            'alteraciones_patron_sueño' => 'boolean',
            'se_duerme_temprano' => 'boolean',
            'se_duerme_tarde' => 'boolean',
            'apnea_sueño' => 'boolean',
            'pesadillas' => 'boolean',
            'enuresis_secundaria' => 'boolean',
            'alteraciones_apetito_detalle' => 'boolean',
            'reflujo' => 'boolean',
            'alteracion_vision' => 'boolean',
            'alteracion_audicion' => 'boolean',
            'alteracion_tacto' => 'boolean',
            'alteraciones_oseas' => 'boolean',
            'alteraciones_musculares' => 'boolean',
            'pie_plano' => 'boolean',
            'referido_escuela_especial' => 'boolean',
            'trajo_epicrisis' => 'boolean',
            'presenta_diagnostico_matricula' => 'boolean',
            'retiro_notificado' => 'boolean',

            // Campos enteros
            'cantidad_hijos' => 'integer',
            'edad_gateo' => 'integer',
            'edad_caminar' => 'integer',
            'edad_hablar' => 'integer'
        ];
    }

    // ======= CONFIGURACIÓN DE AUDITORÍA =======

    /**
     * Campos que deben ser auditados
     */
    protected $auditableFields = [
        'email',
        'codigo_mined',
        'codigo_unico',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'sexo',
        'foto',
        'foto_url',
        'foto_path',
        'foto_uploaded_at',
        'correo_notificaciones',
        'tipo_usuario',

        // ======= Datos de la madre =======
        'nombre_madre',
        'fecha_nacimiento_madre',
        'cedula_madre',
        'religion_madre',
        'estado_civil_madre',
        'telefono_madre',
        'telefono_claro_madre',
        'telefono_tigo_madre',
        'direccion_madre',
        'barrio_madre',
        'ocupacion_madre',
        'lugar_trabajo_madre',
        'telefono_trabajo_madre',

        // ======= Datos del padre =======
        'nombre_padre',
        'fecha_nacimiento_padre',
        'cedula_padre',
        'religion_padre',
        'estado_civil_padre',
        'telefono_padre',
        'telefono_claro_padre',
        'telefono_tigo_padre',
        'direccion_padre',
        'barrio_padre',
        'ocupacion_padre',
        'lugar_trabajo_padre',
        'telefono_trabajo_padre',

        // ======= Responsable =======
        'nombre_responsable',
        'cedula_responsable',
        'telefono_responsable',
        'direccion_responsable',

        // ======= Datos familiares =======
        'cantidad_hijos',
        'lugar_en_familia',
        'personas_hogar',
        'encargado_alumno',
        'contacto_emergencia',
        'telefono_emergencia',
        'metodos_disciplina',
        'pasatiempos_familiares',

        // ======= Área médica / psicológica / social =======
        'personalidad',
        'parto',
        'sufrimiento_fetal',
        'edad_gateo',
        'edad_caminar',
        'edad_hablar',
        'habilidades',
        'pasatiempos',
        'preocupaciones',
        'juegos_preferidos',

        // ======= Área social =======
        'se_relaciona_familiares',
        'establece_relacion_coetaneos',
        'evita_contacto_personas',
        'especifique_evita_personas',
        'evita_lugares_situaciones',
        'especifique_evita_lugares',
        'respeta_figuras_autoridad',

        // ======= Área comunicativa =======
        'atiende_cuando_llaman',
        'es_capaz_comunicarse',
        'comunica_palabras',
        'comunica_señas',
        'comunica_llanto',
        'dificultad_expresarse',
        'especifique_dificultad_expresarse',
        'dificultad_comprender',
        'especifique_dificultad_comprender',
        'atiende_orientaciones',

        // ======= Área psicológica =======
        'estado_animo_general',
        'tiene_fobias',
        'generador_fobia',
        'tiene_agresividad',
        'tipo_agresividad',

        // ======= Área médica detallada =======
        'patologias_detalle',
        'consume_farmacos',
        'farmacos_detalle',
        'tiene_alergias',
        'causas_alergia',
        'alteraciones_patron_sueño',
        'se_duerme_temprano',
        'se_duerme_tarde',
        'apnea_sueño',
        'pesadillas',
        'enuresis_secundaria',
        'alteraciones_apetito_detalle',
        'aversion_alimentos',
        'reflujo',
        'alimentos_favoritos',
        'alteracion_vision',
        'alteracion_audicion',
        'alteracion_tacto',
        'especifique_alteraciones_sentidos',

        // ======= Alteraciones físicas adicionales =======
        'alteraciones_oseas',
        'alteraciones_musculares',
        'pie_plano',

        // ======= Datos especiales =======
        'diagnostico_medico',
        'referido_escuela_especial',
        'trajo_epicrisis',
        'presenta_diagnostico_matricula',

        // ======= Retiro =======
        'fecha_retiro',
        'retiro_notificado',
        'motivo_retiro',
        'informacion_retiro_adicional',

        // ======= Observaciones =======
        'observaciones',

        // ======= Firma =======
        'nombre_persona_firma',
        'cedula_firma'
    ];

    /**
     * Campos que NO deben ser auditados
     */
    protected $nonAuditableFields = [
        'updated_at',
        'created_at',
        'deleted_at',
        'cambios',
        'password',
        'remember_token',
        'email_verified_at',
        'foto_uploaded_at'
    ];

    /**
     * Eventos que deben ser auditados
     */
    protected $auditableEvents = ['created', 'updated', 'deleted'];

    /**
     * Habilitar auditoría granular por campo
     */
    protected $granularAudit = false;

    /**
     * Relación con el modelo Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relaciones de auditoría
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Verificar si el usuario es superadmin
     */
    public function isSuperAdmin(): bool
    {
        return $this->superadmin;
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check role permissions if role exists
        if ($this->role && in_array($permission, $this->role->permisos ?? [])) {
            return true;
        }

        // Permisos por defecto code-level (Espejo de AuthController)
        if (
            in_array($permission, ['ver_mensajes', 'redactar_mensaje']) &&
            in_array($this->tipo_usuario, [self::TIPO_DOCENTE, self::TIPO_ALUMNO, self::TIPO_FAMILIA, self::TIPO_ADMINISTRATIVO, self::TIPO_SUPERUSER])
        ) {
            return true;
        }

        // Avisos (Docentes: Lectura y Escritura por defecto)
        if (
            $this->tipo_usuario === self::TIPO_DOCENTE &&
            in_array($permission, ['avisos.ver', 'avisos.crear', 'avisos.editar', 'avisos.eliminar', 'avisos.estadisticas'])
        ) {
            return true;
        }

        // Avisos (Familias: Solo Lectura)
        if (
            $this->tipo_usuario === self::TIPO_FAMILIA &&
            $permission === 'avisos.ver'
        ) {
            return true;
        }

        // Planes de Clases (Administrativo y SuperUser si no tiene flag)
        if (
            in_array($this->tipo_usuario, [self::TIPO_ADMINISTRATIVO, self::TIPO_SUPERUSER]) &&
            in_array($permission, [
                'agenda.planes_clases.ver_todos',
                'agenda.planes_clases.ver',
                'agenda.planes_clases.editar',
                'agenda.planes_clases.eliminar',
                // Catalogos
                'not_asignatura_grado.index',
                'config_grupos.index',
                'config_grupos.getall',
                'conf_periodo_lectivo.index',
                'conf_periodo_lectivo.getall',
                'config_not_semestre.index',
                'operaciones.docentes',
                'observaciones.ver',
                'observaciones.editar'
            ])
        ) {
            return true;
        }

        // Agenda (Solo ver)
        if (
            $permission === 'agenda.eventos.ver' &&
            in_array($this->tipo_usuario, [self::TIPO_DOCENTE, self::TIPO_ALUMNO, self::TIPO_FAMILIA, self::TIPO_SUPERUSER])
        ) {
            return true;
        }

        // Agenda (Escritura para Docentes)
        if (
            $this->tipo_usuario === self::TIPO_DOCENTE &&
            in_array($permission, [
                'agenda.eventos.crear',
                'agenda.eventos.editar',
                'agenda.eventos.eliminar'
            ])
        ) {
            return true;
        }

        // Planes de Clases (Docentes)
        if (
            $this->tipo_usuario === self::TIPO_DOCENTE &&
            in_array($permission, [
                'agenda.planes_clases.ver',
                'agenda.planes_clases.crear',
                'agenda.planes_clases.editar',
                'agenda.planes_clases.eliminar',
                // Permisos para catalogos necesarios en planes de clases
                'not_asignatura_grado.index',
                'config_grupos.index',
                'config_grupos.getall',
                // Filtros de periodos y parciales
                'conf_periodo_lectivo.index',
                'conf_periodo_lectivo.getall',
                'config_not_semestre.index',
                // Permiso general de operaciones docentes
                'operaciones.docentes',
                'observaciones.ver',
                'observaciones.editar'
            ])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Generar email automáticamente basado en primer_nombre y primer_apellido
     */
    public static function generateUniqueEmail(string $primerNombre, string $primerApellido): string
    {
        // Limpiar y normalizar los nombres
        $primerNombre = strtolower(trim($primerNombre));
        $primerApellido = strtolower(trim($primerApellido));

        // Remover acentos y caracteres especiales
        $primerNombre = self::removeAccents($primerNombre);
        $primerApellido = self::removeAccents($primerApellido);

        // Generar email base
        $baseEmail = $primerNombre . $primerApellido . '@cempp.com';

        // Verificar si el email ya existe
        $counter = 0;
        $email = $baseEmail;

        while (self::where('email', $email)->exists()) {
            $counter++;
            $email = $primerNombre . $primerApellido . $counter . '@cempp.com';
        }

        return $email;
    }

    /**
     * Remover acentos y caracteres especiales
     */
    private static function removeAccents(string $string): string
    {
        $unwanted_array = [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'ā' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'ē' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ī' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'ō' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ū' => 'u',
            'ñ' => 'n',
            'ç' => 'c',
            'Á' => 'A',
            'À' => 'A',
            'Ä' => 'A',
            'Â' => 'A',
            'Ā' => 'A',
            'Ã' => 'A',
            'É' => 'E',
            'È' => 'E',
            'Ë' => 'E',
            'Ê' => 'E',
            'Ē' => 'E',
            'Í' => 'I',
            'Ì' => 'I',
            'Ï' => 'I',
            'Î' => 'I',
            'Ī' => 'I',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ö' => 'O',
            'Ô' => 'O',
            'Ō' => 'O',
            'Õ' => 'O',
            'Ú' => 'U',
            'Ù' => 'U',
            'Ü' => 'U',
            'Û' => 'U',
            'Ū' => 'U',
            'Ñ' => 'N',
            'Ç' => 'C'
        ];

        return strtr($string, $unwanted_array);
    }

    /**
     * Obtener el nombre completo del estudiante
     */
    public function getNombreCompletoAttribute()
    {
        $nombres = trim(($this->primer_nombre ?? '') . ' ' . ($this->segundo_nombre ?? ''));
        $apellidos = trim(($this->primer_apellido ?? '') . ' ' . ($this->segundo_apellido ?? ''));
        return trim($nombres . ' ' . $apellidos);
    }

    /**
     * Accessor para el campo 'name' - concatena nombres y apellidos
     */
    public function getNameAttribute(): string
    {
        return $this->nombre_completo;
    }

    public function getFechaNacimientoEspanolAttribute(): ?string
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }

        return $this->fecha_nacimiento->format('d/m/Y');
    }

    public function getFechaNacimientoMadreEspanolAttribute(): ?string
    {
        if (!$this->fecha_nacimiento_madre) {
            return null;
        }

        return $this->fecha_nacimiento_madre->format('d/m/Y');
    }

    public function getFechaNacimientoPadreEspanolAttribute(): ?string
    {
        if (!$this->fecha_nacimiento_padre) {
            return null;
        }

        return $this->fecha_nacimiento_padre->format('d/m/Y');
    }

    public function getFechaRetiroEspanolAttribute(): ?string
    {
        if (!$this->fecha_retiro) {
            return null;
        }

        return $this->fecha_retiro->format('d/m/Y');
    }

    /**
     * Verificar si es un estudiante
     */
    public function isEstudiante(): bool
    {
        return $this->tipo_usuario === 'alumno';
    }

    /**
     * Verificar si es familia
     */
    public function isFamilia(): bool
    {
        return $this->tipo_usuario === 'familia';
    }

    /**
     * Verificar si es docente
     */
    public function isDocente(): bool
    {
        return $this->tipo_usuario === 'docente';
    }

    /**
     * Relación: aranceles pendientes del usuario (users_aranceles.estado = 'pendiente')
     */
    public function arancelesPendientes()
    {
        return $this->hasMany(UsersAranceles::class, 'user_id')->pendientes()->with(['rubro', 'arancel', 'producto']);
    }

    /**
     * Calcular la edad del alumno basada en su fecha de nacimiento
     */
    public function getEdadAttribute(): ?int
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }

        return $this->fecha_nacimiento->diffInYears(now());
    }

    /**
     * Calcular la edad de la madre basada en su fecha de nacimiento
     */
    public function getEdadMadreAttribute(): ?int
    {
        if (!$this->fecha_nacimiento_madre) {
            return null;
        }

        // Si ya es un objeto Carbon, calcular directamente
        if ($this->fecha_nacimiento_madre instanceof \Carbon\Carbon) {
            return $this->fecha_nacimiento_madre->diffInYears(now());
        }

        // Si es un string, intentar parsearlo con diferentes formatos
        if (is_string($this->fecha_nacimiento_madre)) {
            try {
                // Primero intentar con formato d/m/Y
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $this->fecha_nacimiento_madre)) {
                    $date = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fecha_nacimiento_madre);
                } else {
                    // Para otros formatos, usar parse
                    $date = \Carbon\Carbon::parse($this->fecha_nacimiento_madre);
                }
                return $date->diffInYears(now());
            } catch (\Exception $e) {
                // Si no se puede parsear, retornar null
                return null;
            }
        }

        return null;
    }

    /**
     * Calcular la edad del padre basada en su fecha de nacimiento
     */
    public function getEdadPadreAttribute(): ?int
    {
        if (!$this->fecha_nacimiento_padre) {
            return null;
        }

        // Si ya es un objeto Carbon, calcular directamente
        if ($this->fecha_nacimiento_padre instanceof \Carbon\Carbon) {
            return $this->fecha_nacimiento_padre->diffInYears(now());
        }

        // Si es un string, intentar parsearlo con diferentes formatos
        if (is_string($this->fecha_nacimiento_padre)) {
            try {
                // Primero intentar con formato d/m/Y
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $this->fecha_nacimiento_padre)) {
                    $date = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fecha_nacimiento_padre);
                } else {
                    // Para otros formatos, usar parse
                    $date = \Carbon\Carbon::parse($this->fecha_nacimiento_padre);
                }
                return $date->diffInYears(now());
            } catch (\Exception $e) {
                // Si no se puede parsear, retornar null
                return null;
            }
        }

        return null;
    }
    /**
     * Relación: grupos (historial de matrículas) del usuario
     */
    public function grupos()
    {
        return $this->hasMany(UsersGrupo::class, 'user_id');
    }

    /**
     * Relación: Asignaturas que imparte el docente
     */
    public function asignaturasDocente()
    {
        return $this->hasMany(NotAsignaturaGradoDocente::class, 'user_id');
    }

    /**
     * Relación: Grupos donde es docente guía
     */
    public function gruposComoGuia()
    {
        return $this->hasMany(ConfigGrupo::class, 'docente_guia');
    }

    /**
     * Relación: Hijos (estudiantes) asociados a un usuario familia
     */
    public function hijos()
    {
        return $this->belongsToMany(User::class, 'users_familia', 'familia_id', 'estudiante_id')
            ->whereNull('users_familia.deleted_at')
            ->withTimestamps();
    }
}
