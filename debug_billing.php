<?php

use App\Models\User;
use App\Models\Recibo;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting Billing Debug Script...\n";

    $studentId = 142; // Diego
    $student = User::find($studentId);

    if (!$student) {
        die("Student not found\n");
    }

    echo "Student: {$student->primer_nombre} (ID: $studentId)\n";

    // Simulate Controller Logic
    $perPage = 15;
    $filters = ['user_id' => $studentId];

    echo "Fetching receipts with filters: " . json_encode($filters) . "\n";

    // We can't easily instantiate the service/repo stack without DI,
    // but we can look at what the Repo likely does or use the Model directly to see if queries work.

    $query = Recibo::query();

    // Logic from what we expect in ReciboRepository::getAllPaginated
    if (isset($filters['user_id'])) {
        $query->where('user_id', $filters['user_id']);
    }

    // Add other potential filters likely present in Repo
    $query->with(['usuario', 'detalles.producto', 'detalles.rubro', 'detalles.arancel', 'formasPago']);
    $query->orderBy('created_at', 'desc');

    $recibos = $query->paginate($perPage);

    // Test Serialization
    echo "Testing JSON encoding...\n";
    $json = json_encode($recibos);
    if ($json === false) {
        throw new \Exception("JSON Encode Error: " . json_last_error_msg());
    }
    echo "JSON Encode Successful.\n";

    echo "Found " . $recibos->total() . " receipts.\n";

    foreach ($recibos as $recibo) {
        echo "Recibo #{$recibo->numero_recibo} - Total: {$recibo->total}\n";
    }

    echo "Debug Script Completed Successfully.\n";
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
