<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$audits = App\Models\Audit::where('model_type', 'App\Models\Producto')
    ->where('model_id', 37)
    ->orderBy('audits.created_at', 'asc')
    ->get();

foreach ($audits as $audit) {
    if ($audit->user_id == 325) { // David Cruz
        $old = $audit->old_values['stock_actual'] ?? 'NULL';
        $new = $audit->new_values['stock_actual'] ?? 'NULL';
        echo "ID: {$audit->id} | Old: " . var_export($old, true) . " | New: " . var_export($new, true) . " (Type: " . gettype($new) . ")\n";
    }
}
