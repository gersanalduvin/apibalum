<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Buscando posibles usuarios duplicados (mismo nombre y apellido):\n";

$duplicates = User::select('primer_nombre', 'primer_apellido', DB::raw('count(*) as total'))
    ->groupBy('primer_nombre', 'primer_apellido')
    ->having('total', '>', 1)
    ->get();

echo "Grupos de duplicados encontrados: " . $duplicates->count() . "\n";

foreach ($duplicates as $d) {
    echo "Nombre: {$d->primer_nombre} {$d->primer_apellido} | Cantidad: {$d->total}\n";
    $users = User::where('primer_nombre', $d->primer_nombre)
        ->where('primer_apellido', $d->primer_apellido)
        ->withTrashed()
        ->get();
    
    foreach ($users as $u) {
        echo "  ID: {$u->id} | Email: {$u->email} | Tipo: {$u->tipo_usuario} | Eliminado: " . ($u->deleted_at ?? 'No') . "\n";
    }
}
