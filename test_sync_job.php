<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $familia = \App\Models\User::where('tipo_usuario', 'familia')->first();
    $hijos = $familia->hijos;

    // Dispatch synchronously to see the exception immediately
    \App\Jobs\SendCredencialesFamiliaJob::dispatchSync($familia, 'password123', $hijos);
    echo "Job processed successfully!\n";
} catch (\Exception $e) {
    echo "EXCEPTION THROWN:\n";
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
} catch (\Throwable $e) {
    echo "THROWABLE CAUGHT:\n";
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
