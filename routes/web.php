<?php

use Illuminate\Support\Facades\Route;
use Hamzi\Vaultic\Http\Controllers\WebAuthnController;

$throttleMiddleware = 'throttle:vaultic.passkeys';

$routeChannels = [
    'web' => (array) config('vaultic.routes.web', []),
    'api' => (array) config('vaultic.routes.api', []),
];

foreach ($routeChannels as $channel => $channelConfig) {
    if (! ($channelConfig['enabled'] ?? false)) {
        continue;
    }

    Route::middleware((array) ($channelConfig['middleware'] ?? []))
        ->prefix((string) ($channelConfig['prefix'] ?? 'passkeys'))
        ->name((string) ($channelConfig['name_prefix'] ?? 'vaultic.'))
        ->group(function () use ($channel, $channelConfig, $throttleMiddleware) {
            Route::middleware(array_filter((array) ($channelConfig['authenticated_middleware'] ?? [])))
                ->group(function () use ($channel, $throttleMiddleware) {
                    Route::post('/register/options', [WebAuthnController::class, 'registrationOptions'])
                        ->middleware($throttleMiddleware)
                        ->name('register.options');

                    Route::post('/register', [WebAuthnController::class, 'register'])
                        ->middleware($throttleMiddleware)
                        ->name('register.store');

                    if ($channel === 'web') {
                        Route::delete('/{passkey}', [WebAuthnController::class, 'destroy'])
                            ->name('passkeys.destroy');
                    }
                });

            Route::post('/authenticate/options', [WebAuthnController::class, 'authenticationOptions'])
                ->middleware($throttleMiddleware)
                ->name('authenticate.options');

            Route::post('/authenticate', [WebAuthnController::class, 'authenticate'])
                ->middleware($throttleMiddleware)
                ->name('authenticate.store');
        });
}
