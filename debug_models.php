<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $grupo = \App\Models\ConfigGrupos::first();
    echo "Grupo Turno ID: " . ($grupo ? $grupo->turno_id : 'null') . "\n";

    $aula = \App\Models\ConfigAula::first();
    echo "Aula attributes: " . ($aula ? json_encode($aula->toArray()) : 'null') . "\n";

    $docente = \App\Models\User::whereHas('roles', function ($q) {
        $q->where('name', 'docente');
    })->first();
    echo "Docente attributes: " . ($docente ? json_encode($docente->toArray()) : 'null') . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
