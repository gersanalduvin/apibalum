<?php
require_once __DIR__ . "/vendor/autoload.php";

$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Simular la consulta del endpoint
    $repository = new App\Repositories\ConfigPlanPagoRepository(new App\Models\ConfigPlanPago());
    $result = $repository->getAllPaginated(15);
    
    echo "Consulta exitosa\n";
    echo "Total de registros: " . $result->total() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>