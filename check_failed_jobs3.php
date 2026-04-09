<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failed = \Illuminate\Support\Facades\DB::table('failed_jobs')
    ->orderBy('id', 'desc')
    ->limit(1)
    ->get();

foreach ($failed as $f) {
    echo "ID: " . $f->id . " - " . $f->failed_at . "\n";
    echo substr($f->exception, 0, 3000) . "\n\n=====================\n";
}
