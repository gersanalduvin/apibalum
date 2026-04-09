<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$grupoId = 18; // Based on the screenshot, it's likely this or similar
$results = DB::table('users')
    ->join('users_grupos', 'users.id', '=', 'users_grupos.user_id')
    ->where('users_grupos.grupo_id', $grupoId)
    ->whereNull('users_grupos.deleted_at')
    ->select('users.id', 'users.primer_nombre', 'users.primer_apellido', 'users_grupos.id as ug_id', 'users_grupos.estado', 'users_grupos.deleted_at')
    ->get();

echo "Group $grupoId Students:\n";
foreach ($results as $s) {
    echo $s->id . ': ' . $s->primer_nombre . ' ' . $s->primer_apellido . ' (UG:' . $s->ug_id . ', ST:' . $s->estado . ', DEL:' . ($s->deleted_at ? 'Y' : 'N') . ")\n";
}
