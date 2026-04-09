<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting Debug Script...\n";

    $studentId = 142; // From user screenshot
    $parentEmail = 'juana@cempp.com';
    $parent = User::where('email', $parentEmail)->first();

    if (!$parent) {
        die("Parent not found\n");
    }

    $student = User::find($studentId);
    if (!$student) {
        die("Student not found\n");
    }

    echo "Parent: {$parent->email}, Student: {$student->primer_nombre}\n";

    // 1. Validate Access (Simulated)
    // $controller->validateChildAccess(...) - skipping, we assume access is OK from previous tests

    // 2. Get Active Group
    echo "Loading groups...\n";
    $student->load('grupos.grupo');
    $activeGroup = $student->grupos->sortByDesc('created_at')->first();

    if (!$activeGroup) {
        die("No active group found for student.\n");
    }

    $grupoId = $activeGroup->grupo_id;
    echo "Active Group ID: $grupoId\n";

    // 3. Get Assignments
    echo "Fetching assignments...\n";
    $assignments = \App\Models\NotAsignaturaGradoDocente::where('grupo_id', $grupoId)
        ->with(['asignaturaGrado.materia', 'asignaturaGrado.escala.detalles'])
        ->get();

    echo "Found " . $assignments->count() . " assignments.\n";

    foreach ($assignments as $assignment) {
        $asignaturaNombre = $assignment->asignaturaGrado->materia->nombre ?? 'Desconocida';
        echo "Processing: $asignaturaNombre (ID: {$assignment->id})\n";

        // 4. Get Tasks
        $tasks = \App\Models\NotTarea::where('asignatura_grado_docente_id', $assignment->id)
            ->with(['corte', 'calificaciones' => function ($q) use ($studentId) {
                $q->where('estudiante_id', $studentId);
            }])
            ->get();

        echo "  - Found " . $tasks->count() . " tasks.\n";

        foreach ($tasks as $task) {
            // Check relationship access
            $grade = $task->calificaciones->first();
            if ($grade) {
                echo "    - Grade: {$grade->nota}\n";
            }
        }
    }

    echo "Debug Script Completed Successfully.\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
