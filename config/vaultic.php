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
    ],

    'rate_limit' => [
        'attempts' => (int) env('VAULTIC_RATE_LIMIT_ATTEMPTS', 10),
        'decay_minutes' => (int) env('VAULTIC_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    'routes' => [
        'prefix' => env('VAULTIC_ROUTE_PREFIX', 'passkeys'),
        'middleware' => ['web'],
        'authenticated_middleware' => [env('VAULTIC_AUTH_MIDDLEWARE', 'auth')],
        'name_prefix' => 'vaultic.',
    ],

    'user_model' => env('VAULTIC_USER_MODEL', \App\User::class),
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
