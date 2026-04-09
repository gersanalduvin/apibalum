<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$familia = \App\Models\User::where('tipo_usuario', 'familia')->first();
$hijos = $familia->hijos;

// Dispatch to SQS on a different queue
\App\Jobs\SendCredencialesFamiliaJob::dispatch($familia, 'password', $hijos)->onQueue('test_emails');
echo "Job dispatched to isolated queue!\n";
