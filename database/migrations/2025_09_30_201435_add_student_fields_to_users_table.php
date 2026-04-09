<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ======= Datos del estudiante =======
            $table->string('codigo_mined')->nullable();
            $table->string('codigo_unico')->nullable();
            $table->string('primer_nombre')->nullable();
            $table->string('segundo_nombre')->nullable();
            $table->string('primer_apellido')->nullable();
            $table->string('segundo_apellido')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('lugar_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F'])->nullable();
            $table->string('foto')->nullable();
            $table->string('correo_notificaciones')->nullable();

            // ======= Datos de la madre =======
            $table->string('nombre_madre')->nullable();
            $table->date('fecha_nacimiento_madre')->nullable();
            $table->string('cedula_madre')->nullable();
            $table->string('religion_madre')->nullable();
            $table->enum('estado_civil_madre', [
    'soltera', 'casada', 'divorciada', 'viuda', 'union_libre', 'separada','otro'
])->nullable();
            $table->string('telefono_madre')->nullable();
            $table->string('telefono_claro_madre')->nullable();
            $table->string('telefono_tigo_madre')->nullable();
            $table->string('direccion_madre')->nullable();
            $table->string('barrio_madre')->nullable();
            $table->string('ocupacion_madre')->nullable();
            $table->string('lugar_trabajo_madre')->nullable();
            $table->string('telefono_trabajo_madre')->nullable();

            // ======= Datos del padre =======
            $table->string('nombre_padre')->nullable();
            $table->date('fecha_nacimiento_padre')->nullable();
            $table->string('cedula_padre')->nullable();
            $table->string('religion_padre')->nullable();
            $table->enum('estado_civil_padre', [
    'soltero', 'casado', 'divorciado', 'viudo', 'union_libre', 'separado','otro'
])->nullable();
            $table->string('telefono_padre')->nullable();
            $table->string('telefono_claro_padre')->nullable();
            $table->string('telefono_tigo_padre')->nullable();
            $table->string('direccion_padre')->nullable();
            $table->string('barrio_padre')->nullable();
            $table->string('ocupacion_padre')->nullable();
            $table->string('lugar_trabajo_padre')->nullable();
            $table->string('telefono_trabajo_padre')->nullable();

            // ======= Responsable =======
            $table->string('nombre_responsable')->nullable();
            $table->string('cedula_responsable')->nullable();
            $table->string('telefono_responsable')->nullable();
            $table->string('direccion_responsable')->nullable();

            // ======= Datos familiares =======
            $table->integer('cantidad_hijos')->nullable();
            $table->string('lugar_en_familia')->nullable();
            $table->text('personas_hogar')->nullable();
            $table->string('encargado_alumno')->nullable();
            $table->string('contacto_emergencia')->nullable();
            $table->string('telefono_emergencia')->nullable();
            $table->text('metodos_disciplina')->nullable();
            $table->text('pasatiempos_familiares')->nullable();

            // ======= Área médica / psicológica / social =======
            $table->text('personalidad')->nullable();
            $table->enum('parto', ['natural', 'cesarea'])->nullable();
            $table->boolean('sufrimiento_fetal')->default(false);
            $table->integer('edad_gateo')->nullable();
            $table->integer('edad_caminar')->nullable();
            $table->integer('edad_hablar')->nullable();
            $table->text('habilidades')->nullable();
            $table->text('pasatiempos')->nullable();
            $table->text('preocupaciones')->nullable();
            $table->text('juegos_preferidos')->nullable();

            // ======= Área social =======
            $table->boolean('se_relaciona_familiares')->default(false);
            $table->boolean('establece_relacion_coetaneos')->default(false);
            $table->boolean('evita_contacto_personas')->default(false);
            $table->text('especifique_evita_personas')->nullable();
            $table->boolean('evita_lugares_situaciones')->default(false);
            $table->text('especifique_evita_lugares')->nullable();
            $table->boolean('respeta_figuras_autoridad')->default(false);


            // ======= Área comunicativa =======
            $table->boolean('atiende_cuando_llaman')->default(false);
            $table->boolean('es_capaz_comunicarse')->default(false);
            $table->boolean('comunica_palabras')->default(false);
            $table->boolean('comunica_señas')->default(false);
            $table->boolean('comunica_llanto')->default(false);
            $table->boolean('dificultad_expresarse')->default(false);
            $table->text('especifique_dificultad_expresarse')->nullable();
            $table->boolean('dificultad_comprender')->default(false);
            $table->text('especifique_dificultad_comprender')->nullable();
            $table->boolean('atiende_orientaciones')->default(false);

            // ======= Área psicológica =======
            $table->enum('estado_animo_general', ['alegre', 'triste', 'enojado', 'indiferente'])->nullable();
            $table->boolean('tiene_fobias')->default(false);
            $table->text('generador_fobia')->nullable();
            $table->boolean('tiene_agresividad')->default(false);
            $table->enum('tipo_agresividad', ['encubierta', 'directa'])->nullable();

            // ======= Área médica detallada =======
            $table->text('patologias_detalle')->nullable();
            $table->boolean('consume_farmacos')->default(false);
            $table->text('farmacos_detalle')->nullable();
            $table->boolean('tiene_alergias')->default(false);
            $table->text('causas_alergia')->nullable();
            $table->boolean('alteraciones_patron_sueño')->default(false);
            $table->boolean('se_duerme_temprano')->default(false);
            $table->boolean('se_duerme_tarde')->default(false);
            $table->boolean('apnea_sueño')->default(false);
            $table->boolean('pesadillas')->default(false);
            $table->boolean('enuresis_secundaria')->default(false);
            $table->boolean('alteraciones_apetito_detalle')->default(false);
            $table->text('aversion_alimentos')->nullable();
            $table->boolean('reflujo')->default(false);
            $table->text('alimentos_favoritos')->nullable();
            $table->boolean('alteracion_vision')->default(false);
            $table->boolean('alteracion_audicion')->default(false);
            $table->boolean('alteracion_tacto')->default(false);
            $table->text('especifique_alteraciones_sentidos')->nullable();

            // ======= Alteraciones físicas adicionales =======
            $table->boolean('alteraciones_oseas')->default(false);
            $table->boolean('alteraciones_musculares')->default(false);
            $table->boolean('pie_plano')->default(false);

            // ======= Datos especiales =======
            $table->text('diagnostico_medico')->nullable();
            $table->boolean('referido_escuela_especial')->default(false);
            $table->boolean('trajo_epicrisis')->default(false);
            $table->boolean('presenta_diagnostico_matricula')->default(false);

            // ======= Retiro =======
            $table->date('fecha_retiro')->nullable();
            $table->boolean('retiro_notificado')->default(false);
            $table->text('motivo_retiro')->nullable();
            $table->text('informacion_retiro_adicional')->nullable();

            // ======= Observaciones =======
            $table->text('observaciones')->nullable();

            // ======= Firma =======
            $table->string('nombre_persona_firma')->nullable();
            $table->string('cedula_firma')->nullable();

            $table->enum('tipo_usuario', ['administrativo','superuser','alumno','docente','familia'])->nullable();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar todos los campos agregados en orden inverso
            $table->dropColumn([
                // Tipo de usuario
                'tipo_usuario',

                // Firma
                'cedula_firma',
                'nombre_persona_firma',

                // Observaciones
                'observaciones',

                // Retiro
                'informacion_retiro_adicional',
                'motivo_retiro',
                'retiro_notificado',
                'fecha_retiro',

                // Datos especiales
                'presenta_diagnostico_matricula',
                'trajo_epicrisis',
                'referido_escuela_especial',
                'diagnostico_medico',

                // Alteraciones físicas adicionales
                'pie_plano',
                'alteraciones_musculares',
                'alteraciones_oseas',

                // Área médica detallada
                'especifique_alteraciones_sentidos',
                'alteracion_tacto',
                'alteracion_audicion',
                'alteracion_vision',
                'alimentos_favoritos',
                'reflujo',
                'aversion_alimentos',
                'alteraciones_apetito_detalle',
                'enuresis_secundaria',
                'pesadillas',
                'apnea_sueño',
                'se_duerme_tarde',
                'se_duerme_temprano',
                'alteraciones_patron_sueño',
                'causas_alergia',
                'tiene_alergias',
                'farmacos_detalle',
                'consume_farmacos',
                'patologias_detalle',

                // Área psicológica
                'tipo_agresividad',
                'tiene_agresividad',
                'generador_fobia',
                'tiene_fobias',
                'estado_animo_general',

                // Área comunicativa
                'atiende_orientaciones',
                'especifique_dificultad_comprender',
                'dificultad_comprender',
                'especifique_dificultad_expresarse',
                'dificultad_expresarse',
                'comunica_llanto',
                'comunica_señas',
                'comunica_palabras',
                'es_capaz_comunicarse',
                'atiende_cuando_llaman',

                // Área social
                'respeta_figuras_autoridad',
                'especifique_evita_lugares',
                'evita_lugares_situaciones',
                'especifique_evita_personas',
                'evita_contacto_personas',
                'establece_relacion_coetaneos',
                'se_relaciona_familiares',

                // Área médica / psicológica / social
                'juegos_preferidos',
                'preocupaciones',
                'pasatiempos',
                'habilidades',
                'edad_hablar',
                'edad_caminar',
                'edad_gateo',
                'sufrimiento_fetal',
                'parto',
                'personalidad',

                // Datos familiares
                'pasatiempos_familiares',
                'metodos_disciplina',
                'telefono_emergencia',
                'contacto_emergencia',
                'encargado_alumno',
                'personas_hogar',
                'lugar_en_familia',
                'cantidad_hijos',

                // Responsable
                'direccion_responsable',
                'telefono_responsable',
                'cedula_responsable',
                'nombre_responsable',

                // Datos del padre
                'telefono_trabajo_padre',
                'lugar_trabajo_padre',
                'ocupacion_padre',
                'barrio_padre',
                'direccion_padre',
                'telefono_tigo_padre',
                'telefono_claro_padre',
                'telefono_padre',
                'estado_civil_padre',
                'religion_padre',
                'cedula_padre',
                'fecha_nacimiento_padre',
                'nombre_padre',

                // Datos de la madre
                'telefono_trabajo_madre',
                'lugar_trabajo_madre',
                'ocupacion_madre',
                'barrio_madre',
                'direccion_madre',
                'telefono_tigo_madre',
                'telefono_claro_madre',
                'telefono_madre',
                'estado_civil_madre',
                'religion_madre',
                'cedula_madre',
                'fecha_nacimiento_madre',
                'nombre_madre',

                // Datos del estudiante
                'correo_notificaciones',
                'foto',
                'sexo',
                'lugar_nacimiento',
                'fecha_nacimiento',
                'segundo_apellido',
                'primer_apellido',
                'segundo_nombre',
                'primer_nombre',
                'codigo_unico',
                'codigo_mined'
            ]);
        });
    }
};
