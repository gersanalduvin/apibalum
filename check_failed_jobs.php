<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failed = \Illuminate\Support\Facades\DB::table('failed_jobs')->latest('failed_at')->first();
if ($failed) {
    echo $failed->exception;
} else {
    echo "No failed jobs found.";
}
