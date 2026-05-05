<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$students = User::where('tipo_usuario', 'alumno')
    ->orderBy('primer_apellido')
    ->orderBy('segundo_apellido')
    ->get();

$groups = [];

foreach ($students as $s) {
    $key = trim($s->primer_apellido . ' ' . $s->segundo_apellido);
    if (!isset($groups[$key])) {
        $groups[$key] = [];
    }
    $groups[$key][] = $s;
}

echo "LISTADO DE POSIBLES GRUPOS FAMILIARES (POR APELLIDOS)\n";
echo "====================================================\n\n";

foreach ($groups as $surNames => $members) {
    if (count($members) > 1) {
        echo "APELLIDOS: $surNames (" . count($members) . " estudiantes)\n";
        foreach ($members as $m) {
            echo "  - {$m->primer_nombre} {$m->segundo_nombre} (ID: {$m->id}, Email: {$m->email})\n";
        }
        echo "\n";
    }
}
