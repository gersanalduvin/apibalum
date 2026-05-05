<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$students = User::where('tipo_usuario', 'alumno')->take(50)->get();

foreach ($students as $s) {
    echo "ID: {$s->id} | Name: {$s->primer_nombre} {$s->primer_apellido} | Mother: " . ($s->nombre_madre ?? 'NULL') . " | Father: " . ($s->nombre_padre ?? 'NULL') . "\n";
}
