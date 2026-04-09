<?php
require __DIR__ . '/../vendor/autoload.php';

use Aws\Sqs\SqsClient;

$region = getenv('AWS_DEFAULT_REGION') ?: 'us-east-2';
$key = getenv('AWS_ACCESS_KEY_ID');
$secret = getenv('AWS_SECRET_ACCESS_KEY');
$verify = getenv('AWS_CA_BUNDLE') ?: 'C:\\laragon6\\etc\\ssl\\cacert.pem';

$client = new SqsClient([
    'version' => 'latest',
    'region' => $region,
    'credentials' => [
        'key' => $key,
        'secret' => $secret,
    ],
    'http' => [
        'verify' => $verify,
        'timeout' => 60,
        'connect_timeout' => 60,
        'curl' => [
            CURLOPT_CAINFO => $verify,
        ],
    ],
]);

try {
    $res = $client->listQueues(['QueueNamePrefix' => 'emails']);
    print_r($res->toArray());
    echo "\nOK\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}