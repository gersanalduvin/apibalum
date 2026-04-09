<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'options' => [
            'http' => [
                'verify' => env('AWS_VERIFY_SSL', true) ? (env('AWS_CA_BUNDLE') ?: true) : false,
            ],
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mensajeria' => [
        'notificaciones' => env('NOTIFICACIONES', false),
        'email_mode' => env('NOTIFICACIONES_EMAIL', 0),
    ],

    'gemini' => [
        'key' => env('GOOGLE_GEMINI_API_KEY'),
        'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-1.5-flash'),
    ],

];
