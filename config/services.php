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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'enviatusms' => [
        'api_key' => env('ENVIATUSMS_API_KEY'),
        'base_url' => env('ENVIATUSMS_BASE_URL', 'https://www.enviatusms.com/api'),
        'endpoint_balance' => env('ENVIATUSMS_ENDPOINT_BALANCE', 'balance'),
        'endpoint_send' => env('ENVIATUSMS_ENDPOINT_SEND', 'sms/send'),
        'timeout' => (int) env('ENVIATUSMS_TIMEOUT', 10),
        'retry_times' => (int) env('ENVIATUSMS_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('ENVIATUSMS_RETRY_SLEEP_MS', 200),
    ],

];
