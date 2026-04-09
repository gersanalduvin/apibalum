<?php

namespace App\Providers;

use Aws\S3\S3Client;
use Aws\Ses\SesClient;
use Illuminate\Support\ServiceProvider;

class AwsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register S3 Client
        $this->app->singleton(S3Client::class, function ($app) {
            return new S3Client([
                'credentials' => [
                    'key' => config('aws.credentials.key'),
                    'secret' => config('aws.credentials.secret'),
                ],
                'region' => config('aws.s3.region'),
                'version' => config('aws.version'),
            ]);
        });

        // Register SES Client
        $this->app->singleton(SesClient::class, function ($app) {
            return new SesClient([
                'credentials' => [
                    'key' => config('aws.ses.key'),
                    'secret' => config('aws.ses.secret'),
                ],
                'region' => config('aws.ses.region'),
                'version' => config('aws.version'),
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}