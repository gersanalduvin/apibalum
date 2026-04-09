<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$t = \App\Models\NotTarea::first();
if ($t) {
    echo "Valid Tarea ID: " . $t->id . "\n";
    $u = \App\Models\User::find($t->estudiantes()->first()->id ?? null);
    if ($u) {
        echo "Valid Student ID: " . $u->id . "\n";
    } else {
        // Fallback to any user
        $u = \App\Models\User::first();
        echo "User ID (Fallback): " . $u->id . "\n";
    }
} else {
    echo "No tasks found.\n";
}
