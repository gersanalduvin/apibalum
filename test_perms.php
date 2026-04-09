<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('username', 'LIKE', '%testa%')
    ->orWhere('nombre', 'LIKE', '%testa%')
    ->orWhere('email', 'LIKE', '%testa%')
    ->first();

if ($user) {
    echo json_encode([
        'id' => $user->id,
        'nombre' => $user->nombre ?? '',
        'username' => $user->username ?? '',
        'superadmin' => $user->superadmin,
        'tipo_usuario' => $user->tipo_usuario,
        'roles' => $user->roles->pluck('name'),
        'permissions' => $user->getAllPermissions()->pluck('name')
    ], JSON_PRETTY_PRINT);
} else {
    echo "User not found";
}
