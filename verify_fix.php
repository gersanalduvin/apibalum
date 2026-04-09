<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$repo = app(\App\Repositories\Contracts\CalificacionRepositoryInterface::class);

$users = \App\Models\User::where('primer_nombre', 'like', 'Juan%')
    ->where('primer_apellido', 'like', 'Alfaro%')
    ->get();

foreach ($users as $user) {
    echo "User ID: " . $user->id . " - Name: " . $user->nombre_completo . "\n";
    $grupos = \App\Models\UsersGrupo::withTrashed()->where('user_id', $user->id)->get();
    foreach ($grupos as $ug) {
        echo "  UG ID: " . $ug->id . " - Grupo: " . $ug->grupo_id . " - Estado: " . $ug->estado . " - Deleted: " . ($ug->deleted_at ? 'YES' : 'NO') . "\n";

        // Test repository fetch for this group
        $res = $repo->getGradesByGroupAndSubject($ug->grupo_id, 1, 1);
        $found = $res['students']->where('user_id', $user->id);
        echo "  Repository fetch count for student " . $user->id . " in group " . $ug->grupo_id . ": " . $found->count() . "\n";
    }
}
