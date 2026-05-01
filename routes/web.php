<?php

use Illuminate\Support\Facades\Route;
use Hamzi\Vaultic\Http\Controllers\WebAuthnController;

$throttleMiddleware = 'throttle:vaultic.passkeys';

Route::middleware(config('vaultic.routes.middleware', ['web']))
    ->prefix(config('vaultic.routes.prefix', 'passkeys'))
    ->name(config('vaultic.routes.name_prefix', 'vaultic.'))
    ->group(function () use ($throttleMiddleware) {
        Route::middleware(array_filter(config('vaultic.routes.authenticated_middleware', ['auth'])))
            ->group(function () use ($throttleMiddleware) {
                Route::post('/register/options', [WebAuthnController::class, 'registrationOptions'])
                    ->middleware($throttleMiddleware)
                    ->name('register.options');

                Route::post('/register', [WebAuthnController::class, 'register'])
                    ->middleware($throttleMiddleware)
                    ->name('register.store');
            });

        Route::post('/authenticate/options', [WebAuthnController::class, 'authenticationOptions'])
            ->middleware($throttleMiddleware)
            ->name('authenticate.options');

        Route::post('/authenticate', [WebAuthnController::class, 'authenticate'])
            ->middleware($throttleMiddleware)
            ->name('authenticate.store');
    });
