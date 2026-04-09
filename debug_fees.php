<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting Fees Debug Script...\n";

    $studentId = 142; // Diego

    // Inject Repository
    $repo = $app->make(\App\Repositories\UsersArancelesRepository::class);

    echo "Fetching fees for student ID: $studentId\n";
    $fees = $repo->getByUser($studentId);

    echo "Found " . $fees->count() . " fee records.\n";

    $pending = $fees->where('estado', 'pendiente')->count();
    $paid = $fees->where('estado', 'pagado')->count();

    echo "Pending: $pending, Paid: $paid\n";

    if ($fees->isNotEmpty()) {
        $first = $fees->first();
        echo "Sample Fee: " . ($first->rubro->concepto ?? 'N/A') . " - Amount: " . $first->importe_total . " - Status: " . $first->estado . "\n";
    }

    echo "Debug Script Completed Successfully.\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
