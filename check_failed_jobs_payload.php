<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failed = \Illuminate\Support\Facades\DB::table('failed_jobs')
    ->where('failed_at', '>=', '2026-03-03 06:30:00')
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get();

foreach ($failed as $f) {
    echo "ID: " . $f->id . " - " . $f->failed_at . "\n";
    $payload = json_decode($f->payload, true);

    // Check attempts configuration
    echo "Attempts in payload: " . ($payload['attempts'] ?? 'null') . "\n";
    echo "MaxTries in payload: " . ($payload['maxTries'] ?? 'null') . "\n";
    echo "Timeout in payload: " . ($payload['timeout'] ?? 'null') . "\n";

    // Check command serialization
    $command = unserialize($payload['data']['command']);
    echo "Job Class: " . get_class($command) . "\n";
    echo "Command Tries: " . (property_exists($command, 'tries') ? $command->tries : 'Not set') . "\n";
    echo "\n=====================\n";
}
