<?php

return [
    'rp' => [
        'id' => env('VAULTIC_RP_ID', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost'),
        'name' => env('VAULTIC_RP_NAME', env('APP_NAME', 'Laravel')),
    ],

    'cache' => [
        'store' => env('VAULTIC_CACHE_STORE', 'redis'),
        'prefix' => env('VAULTIC_CACHE_PREFIX', 'vaultic:challenge:'),
        'ttl' => (int) env('VAULTIC_CACHE_TTL', 300),
        'redis_serialize' => (bool) env('VAULTIC_REDIS_SERIALIZE', true),
    ],

    'auth' => [
        'default_guard' => env('VAULTIC_DEFAULT_GUARD', 'web'),
        'session_key' => env('VAULTIC_SESSION_KEY', 'vaultic.passkeys.authenticated'),

        'guards' => [
            'web' => [
                'guard' => env('VAULTIC_WEB_GUARD', 'web'),
                'provider_model' => env('VAULTIC_WEB_USER_MODEL', env('VAULTIC_USER_MODEL', \App\Models\User::class)),
                'identifier_column' => env('VAULTIC_WEB_IDENTIFIER_COLUMN', env('VAULTIC_USER_IDENTIFIER_COLUMN', 'email')),
                'stateful' => true,
                'remember' => false,
            ],

            'api' => [
                'guard' => env('VAULTIC_API_GUARD', 'api'),
                'provider_model' => env('VAULTIC_API_USER_MODEL', env('VAULTIC_USER_MODEL', \App\Models\User::class)),
                'identifier_column' => env('VAULTIC_API_IDENTIFIER_COLUMN', env('VAULTIC_USER_IDENTIFIER_COLUMN', 'email')),
                'stateful' => false,
                'token_issuer' => env('VAULTIC_API_TOKEN_ISSUER', null),
            ],
        ],
    ],

    'rate_limit' => [
        'attempts' => (int) env('VAULTIC_RATE_LIMIT_ATTEMPTS', 10),
        'decay_minutes' => (int) env('VAULTIC_RATE_LIMIT_DECAY_MINUTES', 1),
        'decay_seconds' => (int) env('VAULTIC_RATE_LIMIT_DECAY_SECONDS', 60),
    ],

    'routes' => [
        'web' => [
            'enabled' => (bool) env('VAULTIC_WEB_ROUTES_ENABLED', true),
            'prefix' => env('VAULTIC_WEB_ROUTE_PREFIX', 'passkeys'),
            'middleware' => ['web'],
            'authenticated_middleware' => [env('VAULTIC_WEB_AUTH_MIDDLEWARE', 'auth:web')],
            'name_prefix' => 'vaultic.',
            'guard' => env('VAULTIC_WEB_GUARD', 'web'),
            'stateful' => true,
        ],

        'api' => [
            'enabled' => (bool) env('VAULTIC_API_ROUTES_ENABLED', true),
            'prefix' => env('VAULTIC_API_ROUTE_PREFIX', 'api/passkeys'),
            'middleware' => ['api'],
            'authenticated_middleware' => [env('VAULTIC_API_AUTH_MIDDLEWARE', 'auth:api')],
            'name_prefix' => 'vaultic.api.',
            'guard' => env('VAULTIC_API_GUARD', 'api'),
            'stateful' => false,
        ],
    ],

    'user_model' => env('VAULTIC_USER_MODEL', \App\Models\User::class),
    'user_identifier_column' => env('VAULTIC_USER_IDENTIFIER_COLUMN', 'email'),
    'redirect_after_login' => env('VAULTIC_REDIRECT_AFTER_LOGIN', '/dashboard'),
    'challenge_timeout_ms' => (int) env('VAULTIC_CHALLENGE_TIMEOUT_MS', 60000),
    'device_name_max_length' => (int) env('VAULTIC_DEVICE_NAME_MAX_LENGTH', 100),
    'user_verification' => env('VAULTIC_USER_VERIFICATION', 'preferred'),
    'authenticator_attachment' => env('VAULTIC_AUTHENTICATOR_ATTACHMENT', null),

    'fallback' => [
        'driver' => env('VAULTIC_FALLBACK_DRIVER', 'password'), // password|magic_link
        'password_login_route' => env('VAULTIC_PASSWORD_LOGIN_ROUTE', 'login'),
        'magic_link_route_name' => env('VAULTIC_MAGIC_LINK_ROUTE_NAME', 'login.magic'),
        'magic_link_expire_minutes' => (int) env('VAULTIC_MAGIC_LINK_EXPIRE_MINUTES', 10),
    ],
];
