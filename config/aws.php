<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AWS SDK Configuration
    |--------------------------------------------------------------------------
    |
    | The configuration options set in this file will be passed directly to the
    | `Aws\Sdk` object, from which all client objects are created. The minimum
    | required options are declared here, but the full set of possible options
    | are documented at:
    | http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/configuration.html
    |
    */

    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'version' => 'latest',

    /*
    |--------------------------------------------------------------------------
    | HTTP Configuration
    |--------------------------------------------------------------------------
    */

    'http' => [
        'verify' => env('AWS_VERIFY_SSL', true) ? (env('AWS_CA_BUNDLE') ?: true) : false,
        'timeout' => 60,
        'connect_timeout' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | S3 Configuration
    |--------------------------------------------------------------------------
    */

    's3' => [
        'region' => env('AWS_S3_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'bucket' => env('AWS_S3_BUCKET', env('AWS_BUCKET')),
        'url' => env('AWS_S3_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | SES Configuration
    |--------------------------------------------------------------------------
    */

    'ses' => [
        'region' => env('AWS_SES_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'key' => env('AWS_SES_KEY', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('AWS_SES_SECRET', env('AWS_SECRET_ACCESS_KEY')),
    ],

];