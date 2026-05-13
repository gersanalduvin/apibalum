<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UsersFamilia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateFamilyUsers extends Command
{
    protected $signature = 'app:create-family-users {--dry-run : Ejecutar sin guardar cambios}';
    protected $description = 'Crea usuarios de familia para TODOS los alumnos';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('MODO SIMULACIÓN ACTIVADO');
        }

        // 1. Agrupaciones manuales de las imágenes
        $gruposManuales = [
            ['Anthony Enmanuel Hernandez', 'Javier Antonio Hernandez'],
            ['Denis Antonio Gonzalez', 'Denisse Elizabeth Gonzalez'],
            ['Kristel Abigail Benedith', 'Linda Raquel Benedith'],
            ['Alba Lucia Lobos Sandino', 'Daysi Guadalupe Lobos'],
            ['Sara Noemi Mendoza', 'Dana Adai Mendoza'],
            ['Bianca Nahomi Altamirano', 'Angel Jair Altamirano'],
            ['Diosmar Flores', 'Diomar Martínez'],
            ['Cristopher Adrian Cordero', 'Freddy Jose Cordero'],
            ['Abigail Peralta', 'Fernanda Céspedes'],
            ['Georgina Fernanda Olivas', 'Jashuara Nazareth Membreño'],
            ['Aaron Daniel Vasquez', 'Enyel Gabriel Martinez'],
            ['Dayamanti Sarai Reyes', 'Brayan de Jesus Reyes'],
            ['Thiago Acosta Hernandez', 'Nathan Acosta Hernandez'],
            ['Daniel Romeri', 'Dariel Eliza Romeri'],
            ['Arisbeth Mercedes Mairena', 'Melvin Joshua Mairena'],
            ['Diego Fernando Sampson Valle', 'Mario Sebastian Sampson Valle'],
            ['Dionicio Samuel Chavez', 'Ivan Josue Chavez'],
            ['Carlos Daniel Chavez', 'Juneisi Sofia Manzanares'],
            ['Meredith Alahia Centeno', 'Jeysi Daniela Perez'],
            ['William Lopez Matamoros', 'Yurbin Matamoros'],
            ['Moises David Lopez Sanchez', 'Walter Fabian Lopez Sanchez', 'Hanguel Eduardo Lopez'],
            ['Sharon Jubeysi Torrez', 'Francis Vega'],
            ['Gabriela Ortiz', 'Daniela Ortiz'],
        ];

        $estudiantesProcesadosIds = [];
        $totalFamilias = 0;
        $totalAlumnosVinculados = 0;

        $this->info("Paso 1: Procesando grupos manuales de las imágenes...");

        foreach ($gruposManuales as $grupo) {
            $estudiantesEncontrados = [];
            foreach ($grupo as $nombreCompleto) {
                $estudiante = $this->buscarEstudiante($nombreCompleto);
                if ($estudiante) {
                    $estudiantesEncontrados[] = $estudiante;
                    $estudiantesProcesadosIds[] = $estudiante->id;
                }
            }

            if (!empty($estudiantesEncontrados)) {
                $primerEstudiante = $estudiantesEncontrados[0];
                $email = $this->generarEmail($primerEstudiante);
                $this->crearFamiliaYVincular($email, $primerEstudiante->primer_apellido, $estudiantesEncontrados, $dryRun, $totalFamilias, $totalAlumnosVinculados);
            }
        }

        $this->newLine();
        $this->info("Paso 2: Procesando el resto de los alumnos...");

        // Obtener alumnos que no han sido procesados ni tienen familia
        $alumnosRestantes = User::where('tipo_usuario', 'alumno')
            ->whereNotIn('id', $estudiantesProcesadosIds)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('users_familia')
                      ->whereRaw('users_familia.estudiante_id = users.id');
            })
            ->get();

        $this->info("Alumnos restantes encontrados: " . $alumnosRestantes->count());

        foreach ($alumnosRestantes as $estudiante) {
            $email = $this->generarEmail($estudiante);
            $this->crearFamiliaYVincular($email, $estudiante->primer_apellido, [$estudiante], $dryRun, $totalFamilias, $totalAlumnosVinculados);
        }

        $this->newLine();
        $this->info("Resumen:");
        $this->info("- Familias procesadas: $totalFamilias");
        $this->info("- Alumnos vinculados: $totalAlumnosVinculados");
    }

    private function crearFamiliaYVincular($email, $apellido, $estudiantes, $dryRun, &$totalFamilias, &$totalAlumnosVinculados)
    {
        $nombreFamilia = "Familia " . $this->limpiarApellido($apellido);

        if (!$dryRun) {
            DB::transaction(function () use ($email, $apellido, $estudiantes, &$totalFamilias, &$totalAlumnosVinculados) {
                $userFamilia = User::where('email', $email)->first();

                if (!$userFamilia) {
                    $userFamilia = User::create([
                        'email' => $email,
                        'password' => Hash::make('36251469'),
                        'tipo_usuario' => 'familia',
                        'primer_nombre' => 'Familia',
                        'primer_apellido' => $this->limpiarApellido($apellido),
                        'activo' => true,
                    ]);
                    $totalFamilias++;
                }

                foreach ($estudiantes as $estudiante) {
                    $existeVinculo = UsersFamilia::where('familia_id', $userFamilia->id)
                        ->where('estudiante_id', $estudiante->id)
                        ->exists();

                    if (!$existeVinculo) {
                        UsersFamilia::create([
                            'familia_id' => $userFamilia->id,
                            'estudiante_id' => $estudiante->id,
                            'created_by' => 1,
                        ]);
                        $totalAlumnosVinculados++;
                    }
                }
            });
        } else {
            $totalFamilias++;
            $totalAlumnosVinculados += count($estudiantes);
        }
    }

    private function buscarEstudiante($nombreCompleto)
    {
        $partes = explode(' ', $nombreCompleto);
        $busquedaNombre = substr($partes[0], 0, 4);
        
        $query = User::where('tipo_usuario', 'alumno')
            ->where(function($q) use ($busquedaNombre) {
                $q->where('primer_nombre', 'LIKE', $busquedaNombre . '%')
                  ->orWhere('segundo_nombre', 'LIKE', $busquedaNombre . '%');
            });

        if (count($partes) > 1) {
            $apellidos = array_slice($partes, 1);
            $query->where(function($q) use ($apellidos) {
                foreach ($apellidos as $apellido) {
                    $busq = substr($apellido, 0, 4);
                    $q->orWhere('primer_apellido', 'LIKE', $busq . '%')
                      ->orWhere('segundo_apellido', 'LIKE', $busq . '%')
                      ->orWhere('primer_apellido', 'LIKE', Str::ascii($busq) . '%');
                }
            });
        }

        return $query->first();
    }

    private function limpiarApellido($apellido)
    {
        return Str::title(Str::lower($apellido));
    }

    private function generarEmail($user)
    {
        $inicial = Str::lower(substr(Str::ascii($user->primer_nombre), 0, 1));
        $apellido = Str::lower(Str::ascii($user->primer_apellido));
        $email = $inicial . $apellido . '@balumbotan.com';
        
        $count = 0;
        $originalEmail = $email;
        // En modo real verificamos contra la DB. En dry-run simulamos.
        while (User::where('email', $email)->exists()) {
            $count++;
            $email = str_replace('@balumbotan.com', $count . '@balumbotan.com', $originalEmail);
        }

        return $email;
    }
}
