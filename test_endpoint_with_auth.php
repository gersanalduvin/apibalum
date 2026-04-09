<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\User;

// Crear la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simular una request HTTP con autenticación
$request = Request::create('/api/v1/config-plan-pago', 'GET', [
    'per_page' => 15
]);

// Simular autenticación - obtener el primer usuario admin
try {
    $user = User::where('email', 'admin@admin.com')->first();
    if (!$user) {
        $user = User::first(); // Si no existe admin, usar el primer usuario
    }
    
    if ($user) {
        // Simular autenticación usando Sanctum
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        
        // Agregar token de autorización simulado
        $request->headers->set('Authorization', 'Bearer fake-token-for-testing');
    }
    
    echo "Probando endpoint /api/v1/config-plan-pago con usuario autenticado...\n";
    echo "Usuario: " . ($user ? $user->email : 'No encontrado') . "\n";
    
    // Procesar la request
    $response = $kernel->handle($request);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content Type: " . $response->headers->get('Content-Type') . "\n";
    
    $content = $response->getContent();
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($content, true);
        echo "✅ Endpoint funcionando correctamente\n";
        echo "Total de registros: " . ($data['data']['total'] ?? 'N/A') . "\n";
        echo "Registros por página: " . ($data['data']['per_page'] ?? 'N/A') . "\n";
        
        // Mostrar algunos datos de ejemplo
        if (isset($data['data']['data']) && count($data['data']['data']) > 0) {
            echo "Primer registro:\n";
            $firstRecord = $data['data']['data'][0];
            echo "- ID: " . ($firstRecord['id'] ?? 'N/A') . "\n";
            echo "- Nombre: " . ($firstRecord['nombre'] ?? 'N/A') . "\n";
            echo "- Periodo Lectivo: " . ($firstRecord['periodo_lectivo']['nombre'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ Error en el endpoint\n";
        echo "Response: " . substr($content, 0, 500) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error capturado: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    
    // Mostrar solo las primeras líneas del stack trace
    $trace = explode("\n", $e->getTraceAsString());
    echo "Stack trace (primeras 5 líneas):\n";
    for ($i = 0; $i < min(5, count($trace)); $i++) {
        echo $trace[$i] . "\n";
    }
}

$kernel->terminate($request, $response ?? null);