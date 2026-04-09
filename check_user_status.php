<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ([414, 419] as $id) {
    $u = \App\Models\User::withTrashed()->find($id);
    if ($u) {
        echo $u->id . ': ' . ($u->deleted_at ?: 'ACTIVE') . " - " . $u->nombre_completo . "\n";
    } else {
        echo "User $id not found\n";
    }
}
