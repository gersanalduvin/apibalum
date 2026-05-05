<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UsersFamilia;

echo "--- USER TYPES COUNT ---\n";
$counts = User::select('tipo_usuario', \DB::raw('count(*) as total'))
    ->groupBy('tipo_usuario')
    ->get();
foreach ($counts as $c) {
    echo "Type: {$c->tipo_usuario} | Total: {$c->total}\n";
}

echo "\n--- SAMPLES OF 'familia' USER TYPE (if any) ---\n";
$families = User::where('tipo_usuario', 'familia')->take(10)->get();
foreach ($families as $f) {
    echo "ID: {$f->id} | Name: {$f->name} | Email: {$f->email}\n";
}

echo "\n--- SAMPLE STUDENTS ---\n";
$students = User::where('tipo_usuario', 'alumno')->take(10)->get();
foreach ($students as $s) {
    echo "ID: {$s->id} | Name: {$s->primer_nombre} {$s->primer_apellido} | Email: {$s->email}\n";
}
