<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UsersFamilia;
use Illuminate\Support\Facades\DB;

echo "--- ANALISIS DE ASOCIACIONES EXISTENTES ---\n";
$relations = DB::table('users_familia')
    ->join('users as familia', 'users_familia.familia_id', '=', 'familia.id')
    ->join('users as estudiante', 'users_familia.estudiante_id', '=', 'estudiante.id')
    ->select(
        'familia.primer_nombre as f_p_nombre',
        'familia.primer_apellido as f_p_apellido',
        'familia.email as familia_email',
        'estudiante.primer_nombre as e_p_nombre',
        'estudiante.primer_apellido as e_p_apellido'
    )
    ->whereNull('users_familia.deleted_at')
    ->get();

if ($relations->isEmpty()) {
    echo "No hay asociaciones existentes en users_familia.\n";
} else {
    $currentGroups = [];
    foreach ($relations as $r) {
        $key = $r->f_p_nombre . ' ' . $r->f_p_apellido . ' (' . $r->familia_email . ')';
        $currentGroups[$key][] = $r->e_p_nombre . ' ' . $r->e_p_apellido;
    }
    foreach ($currentGroups as $f => $m) {
        echo "FAMILIA: $f\n";
        foreach ($m as $name) echo "  - $name\n";
    }
}

echo "\n--- ANALISIS DE POSIBLES HERMANOS (POR PADRES) ---\n";
$students = User::where('tipo_usuario', 'alumno')
    ->where(function($q) {
        $q->whereNotNull('nombre_madre')
          ->orWhereNotNull('nombre_padre');
    })
    ->get();

$potentialFamilies = [];
foreach ($students as $s) {
    // We normalize names to find matches
    $mother = trim(strtoupper($s->nombre_madre ?? ''));
    $father = trim(strtoupper($s->nombre_padre ?? ''));
    
    if (!$mother && !$father) continue;
    
    $key = $mother . '|' . $father;
    if (!isset($potentialFamilies[$key])) {
        $potentialFamilies[$key] = [
            'mother' => $s->nombre_madre,
            'father' => $s->nombre_padre,
            'students' => []
        ];
    }
    $potentialFamilies[$key]['students'][] = $s->primer_nombre . ' ' . $s->primer_apellido . ' (ID: ' . $s->id . ')';
}

$foundPotential = false;
foreach ($potentialFamilies as $key => $data) {
    if (count($data['students']) > 1) {
        $foundPotential = true;
        echo "FAMILIA POTENCIAL:\n";
        if ($data['mother']) echo "  MADRE: " . $data['mother'] . "\n";
        if ($data['father']) echo "  PADRE: " . $data['father'] . "\n";
        echo "  ESTUDIANTES:\n";
        foreach ($data['students'] as $st) {
            echo "    - $st\n";
        }
        echo "\n";
    }
}

if (!$foundPotential) {
    echo "No se encontraron grupos de hermanos potenciales por nombre de padres.\n";
}
