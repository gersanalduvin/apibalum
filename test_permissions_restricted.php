<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$roles = ['docente', 'administrativo', 'superuser', 'familia', 'alumno'];
$authController = app(\App\Http\Controllers\AuthController::class);

$reflection = new \ReflectionClass($authController);
$method = $reflection->getMethod('getUserPermissions');
$method->setAccessible(true);

foreach ($roles as $role) {
    echo "\n--- Testing role: $role ---\n";
    $user = \App\Models\User::where('tipo_usuario', $role)->first();
    if (!$user) {
        echo "No users found for role $role\n";
        continue;
    }

    $permisosData = $method->invoke($authController, $user);
    $avisosPerms = array_intersect($permisosData['permisos'], ['avisos.ver', 'avisos.crear', 'avisos.editar', 'avisos.eliminar', 'avisos.estadisticas']);

    echo "Controller Permissions: " . implode(', ', $avisosPerms) . "\n";
    echo "Model hasPermission(avisos.ver): " . ($user->hasPermission('avisos.ver') ? 'Yes' : 'No') . "\n";
}
