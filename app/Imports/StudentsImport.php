<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UsersGrupo;
use App\Models\ConfigGrado;
use App\Models\ConfigGrupo;
use App\Models\ConfPeriodoLectivo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentsImport implements ToCollection, WithHeadingRow
{
    protected $grados;
    protected $grupos;
    protected $periodoActivoId;

    public function __construct()
    {
        // Precargar datos para eficiencia
        $this->grados = ConfigGrado::all();
        $this->grupos = ConfigGrupo::all();
        $this->periodoActivoId = ConfPeriodoLectivo::where('periodo_matricula', 1)->first()?->id 
                                 ?? ConfPeriodoLectivo::orderBy('id', 'desc')->first()?->id;
    }

    protected function normalizeGrado($nombre)
    {
        $nombre = trim($nombre);
        $map = [
            '1er grado' => 'primer grado',
            '2do grado' => 'segundo grado',
            '3er grado' => 'tercer grado',
            '4to grado' => 'cuarto grado',
            '5to grado' => 'quinto grado',
            '6to grado' => 'sexto grado',
            '7mo grado' => 'séptimo grado',
            '8vo grado' => 'octavo grado',
            '9no grado' => 'noveno grado',
            '10mo grado' => 'décimo grado',
            '11mo grado' => 'undécimo grado',
            'i nivel' => 'i nivel',
            'ii nivel' => 'ii nivel',
            'iii nivel' => 'iii nivel',
            'maternal' => 'maternal',
        ];

        $search = strtolower($nombre);
        return $map[$search] ?? $search;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Saltarse filas sin correo
            if (empty($row['correo'])) {
                continue;
            }

            // Evitar duplicados por correo
            if (User::where('email', $row['correo'])->exists()) {
                // \Log::info("Usuario ya existe: " . $row['correo']);
                continue;
            }

            // Buscar Grado con normalización y comparación insensible a acentos
            $gradoNombreNormalizado = Str::slug($this->normalizeGrado($row['grado']));
            $grado = $this->grados->first(function ($g) use ($gradoNombreNormalizado) {
                return Str::slug($g->nombre) === $gradoNombreNormalizado;
            });

            if (!$grado) {
                \Log::warning("Grado no encontrado: " . $gradoNombre);
                continue;
            }

            // Buscar Grupo (primer grupo disponible para ese grado)
            $grupo = $this->grupos->firstWhere('grado_id', $grado->id);

            if (!$grupo) {
                \Log::warning("Grupo no encontrado para grado_id: " . $grado->id);
                continue;
            }

            // Mapear Sexo
            $sexo = strtolower(trim($row['sexo'] ?? ''));
            $sexoDb = ($sexo === 'femenino') ? 'F' : 'M';

            // Crear Usuario
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'primer_nombre' => $row['primer_nombre'],
                'segundo_nombre' => $row['segundo_nombre'] ?? '',
                'primer_apellido' => $row['primer_apellido'],
                'segundo_apellido' => $row['segundo_apellido'] ?? '',
                'email' => $row['correo'],
                'password' => 'password123', // El modelo tiene casting 'hashed'
                'sexo' => $sexoDb,
                'tipo_usuario' => 'alumno',
                'activo' => 1,
                'superadmin' => 0,
            ]);

            // Crear Registro de Grupo/Matrícula
            UsersGrupo::create([
                'user_id' => $user->id,
                'grado_id' => $grado->id,
                'grupo_id' => $grupo->id,
                'periodo_lectivo_id' => $this->periodoActivoId,
                'turno_id' => $grupo->turno_id ?? 1,
                'fecha_matricula' => now(),
                'estado' => 'activo',
                'tipo_ingreso' => 'nuevo_ingreso',
            ]);
        }
    }
}
