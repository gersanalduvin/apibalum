<?php

require_once __DIR__ . '/vendor/autoload.php';

// Configurar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PermissionService;

$service = app(PermissionService::class);
$perms = $service->getAllPermissionsFlatDetailed();

echo "Verificando formato de permisos...\n\n";

$invalidPerms = [];
foreach($perms as $p) {
    if(!preg_match('/^[a-z_]+\.[a-z_]+$/', $p['permiso'])) {
        $invalidPerms[] = $p['permiso'];
    }
}

if(empty($invalidPerms)) {
    echo "✅ Todos los permisos tienen formato válido\n";
} else {
    echo "❌ Permisos con formato inválido:\n";
    foreach($invalidPerms as $perm) {
        echo "   - {$perm}\n";
    }
}

echo "\nTotal permisos: " . count($perms) . "\n";
echo "Permisos inválidos: " . count($invalidPerms) . "\n";