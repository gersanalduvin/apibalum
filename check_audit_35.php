<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$audits = App\Models\Audit::where('model_id', 35)->get();
echo "Audits for Product 35 (Escarapela):\n";
if ($audits->isEmpty()) {
    echo "No audits found.\n";
}
foreach ($audits as $a) {
    echo "ID: " . $a->id . " | User ID: " . $a->user_id . " | Date: " . $a->created_at . " | Old Stock: " . ($a->old_values['stock_actual'] ?? 'N/A') . "\n";
}
