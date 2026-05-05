<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$studentsWithParents = User::where('tipo_usuario', 'alumno')
    ->where(function($q) {
        $q->whereNotNull('nombre_madre')
          ->orWhereNotNull('nombre_padre')
          ->orWhereNotNull('nombre_responsable');
    })
    ->get();

echo "ALUMNOS CON DATOS DE PADRES ENCONTRADOS: " . $studentsWithParents->count() . "\n";
foreach ($studentsWithParents as $s) {
    echo "ID: {$s->id} | {$s->primer_nombre} {$s->primer_apellido} | Madre: {$s->nombre_madre} | Padre: {$s->nombre_padre} | Resp: {$s->nombre_responsable}\n";
}

$allStudents = User::where('tipo_usuario', 'alumno')->get();
$groupsBySurname = [];
foreach ($allStudents as $s) {
    $surname = trim($s->primer_apellido . ' ' . $s->segundo_apellido);
    $groupsBySurname[$surname][] = $s->primer_nombre . ' ' . $s->primer_apellido . ' (ID: ' . $s->id . ')';
}

echo "\nGRUPOS POR APELLIDOS (POTENCIALES FAMILIAS):\n";
foreach ($groupsBySurname as $surname => $members) {
    if (count($members) > 1) {
        echo "[$surname]: " . implode(", ", $members) . "\n";
    }
}
