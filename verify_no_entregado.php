<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\NotCalificacionTarea;

echo "--- Testing Implementation ---\n";

try {
    echo "Checking constants:\n";
    echo "STATUS_NO_ENTREGADO: " . NotCalificacionTarea::STATUS_NO_ENTREGADO . "\n";

    if (NotCalificacionTarea::STATUS_NO_ENTREGADO === 'no_entregado') {
        echo "SUCCESS: Constant is correct.\n";
    } else {
        echo "FAILURE: Constant is incorrect.\n";
    }

    echo "\nAttempting DB update (Tarea 5, Student 174):\n";
    $grade = NotCalificacionTarea::firstOrNew([
        'tarea_id' => 5,
        'estudiante_id' => 174
    ]);
    $grade->estado = 'no_entregado';
    $grade->save();

    $saved = NotCalificacionTarea::where('tarea_id', 5)->where('estudiante_id', 174)->first();
    echo "Saved status: " . $saved->estado . "\n";

    if ($saved->estado === 'no_entregado') {
        echo "SUCCESS: Database saved 'no_entregado' status successfully.\n";
    } else {
        echo "FAILURE: Database did not save requested status.\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
