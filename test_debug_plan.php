<?php
require_once __DIR__ . "/vendor/autoload.php";

$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Probar consulta simple de ConfigPlanPago
    $plan = App\Models\ConfigPlanPago::find(1);
    echo "Plan encontrado: " . $plan->nombre . "\n";
    
    // Probar carga de relación
    $planConRelacion = App\Models\ConfigPlanPago::with("periodoLectivo")->find(1);
    echo "Plan con relación cargado\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>