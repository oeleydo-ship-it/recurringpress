<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // AI providers, keys, models and quota are set in Superadmin → AI. These base URLs are
    // only for local testing against fake APIs.
    'ai' => [
        'anthropic_base_url' => env('AI_ANTHROPIC_BASE_URL'),
        'openai_base_url' => env('AI_OPENAI_BASE_URL'),
        'deepseek_base_url' => env('AI_DEEPSEEK_BASE_URL'),
        'kimi_base_url' => env('AI_KIMI_BASE_URL'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
];
