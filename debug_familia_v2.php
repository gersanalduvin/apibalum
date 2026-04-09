<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'juana@cempp.com';
$user = User::where('email', $email)->first();

echo "User: " . ($user ? $user->email . " (ID: {$user->id})" : "Not Found") . "\n";

if ($user) {
    echo "Testing Direct Query Logic:\n";
    $children = \App\Models\User::select([
        'users.id',
        'users.primer_nombre',
        'users.segundo_nombre',
        'users.primer_apellido',
        'users.segundo_apellido',
        'users.email',
        'users.tipo_usuario',
        'users.codigo_mined',
        'users.codigo_unico'
    ])
        ->join('users_familia as uf', 'uf.estudiante_id', '=', 'users.id')
        ->where('uf.familia_id', $user->id)
        ->whereNull('uf.deleted_at')
        ->where('users.tipo_usuario', 'alumno')
        ->orderBy('users.primer_apellido')
        ->orderBy('users.primer_nombre')
        ->get();

    echo "Found " . $children->count() . " children.\n";
    foreach ($children as $c) {
        echo "- {$c->primer_nombre} {$c->primer_apellido} ({$c->tipo_usuario})\n";
    }
}
