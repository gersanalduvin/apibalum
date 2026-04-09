<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$samantha = User::where('primer_nombre', 'Samantha')->first();

if (!$samantha) {
    echo "Samantha not found\n";
    exit;
}

echo "--- Samantha Data ---\n";
echo "ID: " . $samantha->id . "\n";
echo "foto_url (raw): '" . $samantha->foto_url . "'\n";
echo "foto_path (raw): '" . $samantha->foto_path . "'\n";

// Test the cleaning logic
$cleaned = $samantha->foto_url ? (str_starts_with(trim($samantha->foto_url), 'http') ? str_replace([' ', '\\', "\n", "\r", "\t"], ['', '/', '', '', ''], trim($samantha->foto_url)) : asset($samantha->foto_url)) : null;

echo "Cleaned URL: '" . $cleaned . "'\n";

// Test if it's null when json encoded
echo "JSON Encoded: " . json_encode(['foto_url' => $cleaned]) . "\n";

// Check if there's any hidden attribute or accessor
echo "All attributes keys: " . implode(', ', array_keys($samantha->getAttributes())) . "\n";
