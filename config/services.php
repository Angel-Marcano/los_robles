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

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'reasoner_model' => env('DEEPSEEK_REASONER_MODEL', 'deepseek-reasoner'),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 30),
        'max_tokens' => (int) env('DEEPSEEK_MAX_TOKENS', 1024),
        'temperature' => (float) env('DEEPSEEK_TEMPERATURE', 0.3),
    ],

    'chatbot' => [
        'rate_limit_per_minute' => (int) env('CHATBOT_RATE_LIMIT_PER_MINUTE', 10),
        'max_history_messages' => (int) env('CHATBOT_MAX_HISTORY_MESSAGES', 20),
        'max_operations_per_session' => (int) env('CHATBOT_MAX_OPERATIONS_PER_SESSION', 30),
        'max_operations_per_user_hour' => (int) env('CHATBOT_MAX_OPERATIONS_PER_USER_HOUR', 60),
    ],

];
