<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UsersGrupo;
use App\Models\ConfigGrado;
use App\Models\ConfigGrupo;
use App\Models\ConfigTurnos;
use App\Models\ConfPeriodoLectivo;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class AlumnosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        
        // Obtener datos de configuración existentes
        $grados = ConfigGrado::all();
        $grupos = ConfigGrupo::all();
        $turnos = ConfigTurnos::all();
        $periodoLectivo = ConfPeriodoLectivo::first();
        
        // Verificar que existan datos de configuración
        if ($grados->isEmpty() || $grupos->isEmpty() || $turnos->isEmpty() || !$periodoLectivo) {
            $this->command->error('No se encontraron datos de configuración necesarios (grados, grupos, turnos, periodo lectivo)');
            return;
        }
        
        $this->command->info('Creando 30 usuarios tipo alumno...');
        
        for ($i = 1; $i <= 30; $i++) {
            // Generar datos del alumno
            $sexo = $faker->randomElement([User::SEXO_MASCULINO, User::SEXO_FEMENINO]);
            $primerNombre = $sexo === User::SEXO_MASCULINO ? $faker->firstNameMale : $faker->firstNameFemale;
            $segundoNombre = $sexo === User::SEXO_MASCULINO ? $faker->firstNameMale : $faker->firstNameFemale;
            $primerApellido = $faker->lastName;
            $segundoApellido = $faker->lastName;
            
            // Crear usuario
            $user = User::create([
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password123'),
                'tipo_usuario' => 'alumno',
                'superadmin' => false,
                
                // Datos básicos del estudiante
                'codigo_mined' => 'MIN' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'codigo_unico' => 'ALU' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'primer_nombre' => $primerNombre,
                'segundo_nombre' => $segundoNombre,
                'primer_apellido' => $primerApellido,
                'segundo_apellido' => $segundoApellido,
                'fecha_nacimiento' => $faker->dateTimeBetween('-18 years', '-6 years')->format('Y-m-d'),
                'lugar_nacimiento' => $faker->city,
                'sexo' => $sexo,
                'correo_notificaciones' => $faker->safeEmail,
                
                // Datos de la madre
                'nombre_madre' => $faker->name('female'),
                'fecha_nacimiento_madre' => $faker->dateTimeBetween('-50 years', '-25 years')->format('Y-m-d'),
                'cedula_madre' => $faker->numerify('###-######-####A'),
                'religion_madre' => $faker->randomElement(['Católica', 'Evangélica', 'Testigo de Jehová', 'Otra']),
                'estado_civil_madre' => $faker->randomElement([
                    User::ESTADO_CIVIL_MADRE_SOLTERA,
                    User::ESTADO_CIVIL_MADRE_CASADA,
                    User::ESTADO_CIVIL_MADRE_UNION_LIBRE,
                    User::ESTADO_CIVIL_MADRE_DIVORCIADA,
                    User::ESTADO_CIVIL_MADRE_VIUDA
                ]),
                'telefono_madre' => $faker->phoneNumber,
                'direccion_madre' => $faker->address,
                'barrio_madre' => $faker->streetName,
                'ocupacion_madre' => $faker->jobTitle,
                
                // Datos del padre
                'nombre_padre' => $faker->name('male'),
                'fecha_nacimiento_padre' => $faker->dateTimeBetween('-55 years', '-25 years')->format('Y-m-d'),
                'cedula_padre' => $faker->numerify('###-######-####A'),
                'religion_padre' => $faker->randomElement(['Católica', 'Evangélica', 'Testigo de Jehová', 'Otra']),
                'estado_civil_padre' => $faker->randomElement([
                    User::ESTADO_CIVIL_PADRE_SOLTERO,
                    User::ESTADO_CIVIL_PADRE_CASADO,
                    User::ESTADO_CIVIL_PADRE_UNION_LIBRE,
                    User::ESTADO_CIVIL_PADRE_DIVORCIADO,
                    User::ESTADO_CIVIL_PADRE_VIUDO
                ]),
                'telefono_padre' => $faker->phoneNumber,
                'direccion_padre' => $faker->address,
                'barrio_padre' => $faker->streetName,
                'ocupacion_padre' => $faker->jobTitle,
                
                // Responsable
                'nombre_responsable' => $faker->name,
                'cedula_responsable' => $faker->numerify('###-######-####A'),
                'telefono_responsable' => $faker->phoneNumber,
                'direccion_responsable' => $faker->address,
                
                // Datos familiares
                'cantidad_hijos' => $faker->numberBetween(1, 5),
                'lugar_en_familia' => $faker->numberBetween(1, 5),
                'personas_hogar' => $faker->numberBetween(2, 8),
                'encargado_alumno' => $faker->randomElement(['Madre', 'Padre', 'Abuelos', 'Tíos', 'Otros']),
                'contacto_emergencia' => $faker->name,
                'telefono_emergencia' => $faker->phoneNumber,
                
                // Área médica básica
                'personalidad' => $faker->randomElement(['Extrovertido', 'Introvertido', 'Tímido', 'Sociable']),
                'parto' => $faker->randomElement([User::PARTO_NATURAL, User::PARTO_CESAREA]),
                'sufrimiento_fetal' => $faker->boolean(20),
                'edad_gateo' => $faker->numberBetween(6, 12),
                'edad_caminar' => $faker->numberBetween(10, 18),
                'edad_hablar' => $faker->numberBetween(12, 24),
                
                // Área social
                'se_relaciona_familiares' => $faker->boolean(80),
                'establece_relacion_coetaneos' => $faker->boolean(70),
                'evita_contacto_personas' => $faker->boolean(20),
                'respeta_figuras_autoridad' => $faker->boolean(85),
                
                // Área comunicativa
                'atiende_cuando_llaman' => $faker->boolean(90),
                'es_capaz_comunicarse' => $faker->boolean(95),
                'comunica_palabras' => $faker->boolean(90),
                'comunica_señas' => $faker->boolean(30),
                'comunica_llanto' => $faker->boolean(40),
                'atiende_orientaciones' => $faker->boolean(80),
                
                // Área psicológica
                'estado_animo_general' => $faker->randomElement([
                    User::ESTADO_ANIMO_ALEGRE,
                    User::ESTADO_ANIMO_TRISTE,
                    User::ESTADO_ANIMO_ENOJADO,
                    User::ESTADO_ANIMO_INDIFERENTE
                ]),
                'tiene_fobias' => $faker->boolean(15),
                'tiene_agresividad' => $faker->boolean(10),
                'tipo_agresividad' => $faker->randomElement([User::TIPO_AGRESIVIDAD_ENCUBIERTA, User::TIPO_AGRESIVIDAD_DIRECTA]),
                
                // Campos de auditoría
                'created_by' => 1, // Asumiendo que existe un usuario admin con ID 1
                'updated_by' => 1,
            ]);
            
            // Crear matrícula en users_grupos
            $grupoAleatorio = $grupos->random();
            $turnoAleatorio = $turnos->random();
            $gradoAleatorio = $grados->random();
            
            UsersGrupo::create([
                'user_id' => $user->id,
                'fecha_matricula' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'periodo_lectivo_id' => $periodoLectivo->id,
                'grado_id' => $gradoAleatorio->id,
                'grupo_id' => $grupoAleatorio->id,
                'turno_id' => $turnoAleatorio->id,
                'numero_recibo' => 'REC' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'tipo_ingreso' => $faker->randomElement(['nuevo_ingreso', 'reingreso']),
                'estado' => 'activo',
                'activar_estadistica' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
            
            $this->command->info("Creado alumno {$i}/30: {$primerNombre} {$primerApellido}");
        }
        
        $this->command->info('¡Seeder completado! Se crearon 30 alumnos con sus respectivas matrículas.');
    }
}