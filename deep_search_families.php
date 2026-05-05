<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\UsersFamilia;

echo "Searching for family users...\n";
$users = User::withTrashed()
    ->where(function($q) {
        $q->where('tipo_usuario', 'familia')
          ->orWhere('email', 'like', '%cempp.com%')
          ->orWhere('email', 'like', '%familia%');
    })
    ->get();

foreach ($users as $u) {
    $status = $u->deleted_at ? "[DELETED]" : "[ACTIVE]";
    echo "ID: {$u->id} | Name: {$u->name} | Email: {$u->email} | Type: {$u->tipo_usuario} $status\n";
}

echo "\nChecking associations counts in users_familia...\n";
$countTotal = UsersFamilia::count();
$countDeleted = UsersFamilia::onlyTrashed()->count();
echo "Total Associations: $countTotal\n";
echo "Deleted Associations: $countDeleted\n";

if ($countTotal > 0) {
    echo "\nSample Associations:\n";
    $samples = UsersFamilia::with(['familia', 'estudiante'])->take(5)->get();
    foreach ($samples as $s) {
        $faname = $s->familia ? ($s->familia->primer_nombre . ' ' . $s->familia->primer_apellido) : "Unknown";
        $stname = $s->estudiante ? ($s->estudiante->primer_nombre . ' ' . $s->estudiante->primer_apellido) : "Unknown";
        echo "Family: $faname -> Student: $stname\n";
    }
}
