<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$estudianteId = 295;
$asignaturaGradoId = 6; // To be confirmed

// Fetch all task assignments for this student
$assignments = DB::table('not_tarea_estudiantes as nte')
    ->join('users_grupos as ug', 'nte.users_grupo_id', '=', 'ug.id')
    ->join('not_tareas as nt', 'nte.tarea_id', '=', 'nt.id')
    ->where('ug.user_id', $estudianteId)
    ->select('nt.id', 'nt.nombre', 'ug.grupo_id')
    ->get();

echo "Assignments for Student 295:\n";
print_r($assignments->toArray());

// Fetch all grades for this student
$grades = DB::table('not_calificaciones_tareas')
    ->where('estudiante_id', $estudianteId)
    ->get();

echo "\nGrades for Student 295:\n";
print_r($grades->toArray());
