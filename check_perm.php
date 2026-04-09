<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::find(387);
if (!$user) {
    echo "User 387 not found\n";
    exit;
}

echo "User: {$user->id} - {$user->email}\n";
echo "Tipo: {$user->tipo_usuario}\n";
echo "Role ID: " . ($user->role_id ?? 'NULL') . "\n";
echo "Role Name: " . ($user->role?->nombre ?? 'NONE') . "\n";
echo "Has 'redactar_mensaje': " . ($user->hasPermission('redactar_mensaje') ? 'YES' : 'NO') . "\n";
echo "Has 'ver_mensajes': " . ($user->hasPermission('ver_mensajes') ? 'YES' : 'NO') . "\n";
echo "Has 'avisos.ver': " . ($user->hasPermission('avisos.ver') ? 'YES' : 'NO') . "\n";
# echo "Permissions array: " . json_encode($user->role?->permisos) . "\n";
