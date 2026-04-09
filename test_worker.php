<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$queue = \Illuminate\Support\Facades\Queue::connection('sqs');
$job = $queue->pop('emails');

if ($job) {
    echo "Found job: " . $job->getJobId() . "\n";
    try {
        $job->fire();
        echo "Job fired successfully.\n";
    } catch (\Exception $e) {
        echo "EXCEPTION:\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString();
    }
} else {
    echo "No jobs in queue.\n";
}
