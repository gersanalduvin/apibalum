<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simular una request HTTP
$request = Request::create('/api/v1/config-plan-pago', 'GET', [
    'per_page' => 15
]);

try {
    echo "Probando endpoint /api/v1/config-plan-pago...\n";
    
    // Procesar la request
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content Type: " . $response->headers->get('Content-Type') . "\n";
    
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Endpoint funcionando correctamente\n";
        echo "Total de registros: " . ($data['data']['total'] ?? 'N/A') . "\n";
        echo "Registros por página: " . ($data['data']['per_page'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Error en el endpoint\n";
        echo "Response: " . $content . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error capturado: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? null);