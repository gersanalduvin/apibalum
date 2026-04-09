<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ConfigGrupo;
use App\Models\ConfPeriodoLectivo;

$activePeriod = ConfPeriodoLectivo::where('periodo_nota', 1)->first();
echo "Active Period: " . ($activePeriod ? $activePeriod->id . " - " . $activePeriod->nombre : "NONE") . "\n";

$tipos = User::distinct()->pluck('tipo_usuario');
echo "Distinct types: " . $tipos->implode(', ') . "\n\n";

$teachers = User::whereIn('tipo_usuario', ['docente', 'docente-guia', 'Profesor'])->get();
echo "Total teachers found with those types: " . $teachers->count() . "\n";

foreach ($teachers as $t) {
    echo "Teacher: [{$t->id}] {$t->email} ({$t->tipo_usuario})\n";
    $gruposGuia = ConfigGrupo::where('docente_guia', $t->id)
        ->whereHas('periodoLectivo', fn($q) => $q->where('periodo_nota', 1))
        ->get()
        ->pluck('id');

    $gruposAsignados = ConfigGrupo::whereHas('asignaturasDocente', fn($q) => $q->where('user_id', $t->id))
        ->whereHas('periodoLectivo', fn($q) => $q->where('periodo_nota', 1))
        ->get()
        ->pluck('id');

    $allGroups = $gruposGuia->merge($gruposAsignados)->unique();
    if ($allGroups->isNotEmpty()) {
        echo "  Groups: " . $allGroups->implode(', ') . "\n";
    } else {
        echo "  Groups: NONE\n";
    }
}
