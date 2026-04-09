<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$repo = app(\App\Repositories\Contracts\CalificacionRepositoryInterface::class);

$grupoId = 18;
$res = $repo->getGradesByGroupAndSubject($grupoId, 1, 1);
$students = $res['students'];

echo "Repository fetched " . $students->count() . " students for group $grupoId.\n";
foreach ($students as $s) {
    if (str_contains($s->nombre_completo, 'Juan Avener Alfaro Lacayo')) {
        echo "FOUND: ID " . $s->user_id . " - " . $s->nombre_completo . " (UG ID: " . $s->users_grupo_id . ")\n";
    }
}
