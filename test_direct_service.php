<?php

require_once __DIR__ . '/vendor/autoload.php';

// Configurar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Configurar la aplicación
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Probando ConfigPlanPagoService directamente...\n";
    
    // Obtener el servicio
    $service = app(\App\Services\ConfigPlanPagoService::class);
    
    echo "✅ Servicio obtenido correctamente\n";
    
    // Probar el método getAllPaginated
    echo "Probando getAllPaginated(15)...\n";
    $result = $service->getAllPaginated(15);
    
    echo "✅ Método getAllPaginated ejecutado correctamente\n";
    echo "Total de registros: " . $result->total() . "\n";
    echo "Registros por página: " . $result->perPage() . "\n";
    echo "Página actual: " . $result->currentPage() . "\n";
    
    // Mostrar algunos datos de ejemplo
    $items = $result->items();
    if (count($items) > 0) {
        echo "Primer registro:\n";
        $firstRecord = $items[0];
        echo "- ID: " . $firstRecord->id . "\n";
        echo "- Nombre: " . $firstRecord->nombre . "\n";
        echo "- Periodo Lectivo: " . ($firstRecord->periodoLectivo ? $firstRecord->periodoLectivo->nombre : 'N/A') . "\n";
        echo "- Estado: " . ($firstRecord->estado ? 'Activo' : 'Inactivo') . "\n";
    }
    
    echo "\n✅ Todas las pruebas pasaron correctamente\n";
    
} catch (Exception $e) {
    echo "❌ Error capturado: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    
    // Mostrar solo las primeras líneas del stack trace
    $trace = explode("\n", $e->getTraceAsString());
    echo "Stack trace (primeras 10 líneas):\n";
    for ($i = 0; $i < min(10, count($trace)); $i++) {
        echo $trace[$i] . "\n";
    }
}