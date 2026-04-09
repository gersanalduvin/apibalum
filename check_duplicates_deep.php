<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$name = 'Juan Avener Alfaro Lacayo';
// split into parts
$parts = explode(' ', $name);
$query = \App\Models\User::query();
foreach ($parts as $part) {
    if ($part) {
        $query->where(function ($q) use ($part) {
            $q->where('primer_nombre', 'like', "%$part%")
                ->orWhere('segundo_nombre', 'like', "%$part%")
                ->orWhere('primer_apellido', 'like', "%$part%")
                ->orWhere('segundo_apellido', 'like', "%$part%");
        });
    }
}

$users = $query->get();
echo "Found " . $users->count() . " users matching the name parts.\n";
foreach ($users as $user) {
    echo "User ID: " . $user->id . " - " . $user->nombre_completo . " (Deleted: " . ($user->deleted_at ? 'YES' : 'NO') . ")\n";
    $grupos = \App\Models\UsersGrupo::withTrashed()->where('user_id', $user->id)->get();
    foreach ($grupos as $ug) {
        echo "  UG ID: " . $ug->id . " - Grupo ID: " . $ug->grupo_id . " - Estado: " . $ug->estado . " - Deleted: " . ($ug->deleted_at ? 'YES' : 'NO') . "\n";
    }
}
