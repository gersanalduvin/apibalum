<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;

echo "--- ROLES ---\n";
$roles = Role::all();
foreach ($roles as $r) {
    echo "ID: {$r->id} | Name: {$r->nombre}\n";
}

echo "\n--- USER SAMPLES FOR SCHEMA CHECK ---\n";
$u = User::first();
if ($u) {
    echo "First user ID: {$u->id} | Name: {$u->primer_nombre}\n";
    // Check if 'name' exists in DB or just as appends
    $columns = \Schema::getColumnListing('users');
    echo "Columns in users table: " . implode(", ", $columns) . "\n";
}
