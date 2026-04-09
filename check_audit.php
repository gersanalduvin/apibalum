<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$audits = App\Models\Audit::where('model_id', 29)->get();
echo "Audits for Product 29:\n";
foreach ($audits as $a) {
    echo "ID: " . $a->id . " | User ID: " . $a->user_id . " | Date: " . $a->created_at . "\n";
}
