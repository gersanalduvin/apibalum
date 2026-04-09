<?php

use App\Models\Audit;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Buscando cambios donde la beca fue reseteada a 0 (HISTORIAL COMPLETO):\n";

$audits = Audit::where('model_type', 'App\Models\UsersAranceles')
    ->where(function($q) {
        $q->where('new_values', 'like', '%"beca":0%')
          ->orWhere('new_values', 'like', '%"beca":"0"%')
          ->orWhere('new_value', '0');
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total auditorías encontradas: " . $audits->count() . "\n";

foreach ($audits as $audit) {
    if (isset($audit->old_values['beca']) && (float)$audit->old_values['beca'] > 0) {
        echo "RESET - ID: {$audit->id} | Modelo: #{$audit->model_id} | Usuario: {$audit->user_name} | Fecha: {$audit->created_at}\n";
        echo "  Beca: " . $audit->old_values['beca'] . " -> 0\n";
    } elseif ($audit->column_name === 'beca' && (float)$audit->old_value > 0) {
        echo "RESET - ID: {$audit->id} | Modelo: #{$audit->model_id} | Usuario: {$audit->user_name} | Fecha: {$audit->created_at} (Granular)\n";
        echo "  Beca: " . $audit->old_value . " -> 0\n";
    }
}
