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
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'ollama'),
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://ollama:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.1:8b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 600),
        'pull_timeout' => (int) env('OLLAMA_PULL_TIMEOUT', 600),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', '1h'),
        'bearer_token' => env('OLLAMA_BEARER_TOKEN'),
    ],

    'openclaw' => [
        'base_url' => env('OPENCLAW_BASE_URL'),
        'model' => env('OPENCLAW_MODEL', 'openclaw/default'),
        'timeout' => (int) env('OPENCLAW_TIMEOUT', 600),
        'bearer_token' => env('OPENCLAW_BEARER_TOKEN'),
    ],

];
