<?php

require __DIR__ . '/vendor/autoload.php';
\$app = require_once __DIR__ . '/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

use App\Models\UsersFamilia;
use App\Models\User;

\$associations = UsersFamilia::with(['familia', 'estudiante'])->get();

\$report = [];

foreach (\$associations as \$a) {
    if (!\$a->familia || !\$a->estudiante) continue;
    
    \$familiaKey = \$a->familia->name . ' (' . \$a->familia->email . ')';
    if (!isset(\$report[\$familiaKey])) {
        \$report[\$familiaKey] = [];
    }
    \$report[\$familiaKey][] = \$a->estudiante->primer_nombre . ' ' . \$a->estudiante->primer_apellido . ' (ID: ' . \$a->estudiante->id . ')';
}

echo \"LISTADO DE FAMILIAS Y ESTUDIANTES ASOCIADOS\n\";
echo \"========================================\n\n\";

foreach (\$report as \$familia => \$estudiantes) {
    echo \"FAMILIA: \$familia\n\";
    foreach (\$estudiantes as \$est) {
        echo \"  - \$est\n\";
    }
    echo \"\n\";
}
