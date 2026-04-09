<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting Schedule Debug Script...\n";

    $studentId = 142; // From user screenshot
    $student = User::find($studentId);

    if (!$student) {
        die("Student not found\n");
    }

    echo "Student: {$student->primer_nombre} (ID: $studentId)\n";

    // 1. Get Active Group
    echo "Loading active group...\n";
    $student->load('grupos.grupo');
    $activeGroup = $student->grupos->sortByDesc('created_at')->first();

    if (!$activeGroup) {
        die("No active group found for student.\n");
    }

    $grupoId = $activeGroup->grupo_id;
    $periodoId = $activeGroup->grupo->periodo_lectivo_id;
    echo "Active Group ID: $grupoId, Period ID: $periodoId\n";

    // 2. Query HorarioClase directly
    echo "Fetching schedule...\n";

    $horario = \App\Models\HorarioClase::where('grupo_id', $grupoId)
        ->whereNull('deleted_at')
        ->get();

    echo "Found " . $horario->count() . " schedule blocks.\n";

    // 3. Load Relations
    echo "Loading relations...\n";
    // Fixed: Removed 'asignaturaGrado.asignatura' which does not exist
    $horario->load(['asignaturaGrado.materia', 'docente', 'aula']);

    foreach ($horario as $block) {
        echo "Block ID: {$block->id}\n";

        $materia = $block->titulo_personalizado;
        if (!$materia && $block->asignaturaGrado) {
            // Fixed: Removed fallback to 'asignatura' relation
            $materia = $block->asignaturaGrado->materia->nombre ?? 'N/A';
        }
        echo "  - Materia: $materia\n";
    }

    echo "Debug Script Completed Successfully.\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
