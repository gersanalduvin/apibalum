<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\NotAsignaturaGrado;
use App\Models\ConfigGrupo;
use App\Models\NotAsignaturaGradoDocente;
use App\Models\User;

$a = NotAsignaturaGrado::whereHas('materia', function ($q) {
    $q->where('nombre', 'like', '%Creciendo en Valores%');
})->first();

if (!$a) {
    echo "Asignatura no encontrada\n";
    exit;
}

$g = ConfigGrupo::where('periodo_lectivo_id', $a->periodo_lectivo_id)
    ->whereHas('grado', function ($q) {
        $q->where('nombre', 'like', '%1 GRADO%');
    })
    ->whereHas('seccion', function ($q) {
        $q->where('nombre', 'like', '%A%');
    })
    ->first();

if (!$g) {
    echo "Grupo no encontrado\n";
    exit;
}

$as = NotAsignaturaGradoDocente::withTrashed()
    ->where('asignatura_grado_id', $a->id)
    ->where('grupo_id', $g->id)
    ->first();

if (!$as) {
    echo "Asignacion no encontrada\n";
    exit;
}

$d = User::withTrashed()->find($as->user_id);

echo "Asignatura: " . ($a->materia->nombre ?? 'N/A') . "\n";
echo "Grupo: " . ($g->nombre ?? 'N/A') . "\n";
echo "Docente: " . ($d ? ($d->primer_nombre . ' ' . $d->primer_apellido) : 'Nulo') . " (ID: " . $as->user_id . ")\n";
echo "Docente_Eliminado: " . ($d && $d->trashed() ? 'SI' : 'NO') . "\n";
echo "Asignacion_Eliminada: " . ($as->trashed() ? 'SI' : 'NO') . "\n";
