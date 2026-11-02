<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'superadmin' => [
            'driver' => 'session',
            'provider' => 'superadmins',
        ],
        'client' => [
            'driver' => 'session',
            'provider' => 'client_accounts',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'superadmins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Superadmin::class,
        ],
        'client_accounts' => [
            'driver' => 'eloquent',
            'model' => App\Models\ClientAccount::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'superadmins' => [
            'provider' => 'superadmins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
