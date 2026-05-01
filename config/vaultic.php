<?php

return [
    'rp' => [
        'id' => parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
        'name' => env('APP_NAME', 'Laravel'),
    ],

    'cache' => [
        'store' => config('cache.default', 'file'),
        'prefix' => 'vaultic:challenge:',
        'ttl' => 300,
        'redis_serialize' => true,
    ],

    'auth' => [
        'default_guard' => 'web',
        'session_key' => 'vaultic.passkeys.authenticated',

        'guards' => [
            'web' => [
                'guard' => 'web',
                'provider_model' => \App\Models\User::class,
                'identifier_column' => 'email',
                'stateful' => true,
                'remember' => false,
            ],

            'api' => [
                'guard' => 'api',
                'provider_model' => \App\Models\User::class,
                'identifier_column' => 'email',
                'stateful' => false,
                'token_issuer' => null,
            ],
        ],
    ],

    'rate_limit' => [
        'attempts' => 10,
        'decay_minutes' => 1,
        'decay_seconds' => 60,
    ],

    'routes' => [
        'web' => [
            'enabled' => true,
            'prefix' => 'passkeys',
            'middleware' => ['web'],
            'authenticated_middleware' => ['auth:web'],
            'name_prefix' => 'vaultic.',
            'guard' => 'web',
            'stateful' => true,
        ],

        'api' => [
            'enabled' => true,
            'prefix' => 'api/passkeys',
            'middleware' => ['api'],
            'authenticated_middleware' => ['auth:api'],
            'name_prefix' => 'vaultic.api.',
            'guard' => 'api',
            'stateful' => false,
        ],
    ],

    'user_model' => \App\Models\User::class,
    'user_identifier_column' => 'email',
    'redirect_after_login' => '/dashboard',
    'challenge_timeout_ms' => 60000,
    'device_name_max_length' => 100,
    'user_verification' => 'preferred',
    'resident_key' => 'required',
    'authenticator_attachment' => null,
    'authenticator_hints' => ['client-device', 'hybrid'],

    'fallback' => [
        'driver' => 'password', // password|magic_link
        'password_login_route' => 'login',
        'magic_link_route_name' => 'login.magic',
        'magic_link_expire_minutes' => 10,
    ],
];
