<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Hamzi\Vaultic\Http\Controllers\WebAuthnController;

Route::middleware(config('vaultic.routes.middleware', ['web']))
    ->prefix(config('vaultic.routes.prefix', 'passkeys'))
    ->name(config('vaultic.routes.name_prefix', 'vaultic.'))
    ->group(function (): void {
        Route::middleware(array_filter(config('vaultic.routes.authenticated_middleware', ['auth'])))
            ->group(function (): void {
                Route::post('/register/options', [WebAuthnController::class, 'registrationOptions'])
                    ->middleware('throttle:vaultic.passkeys')
                    ->name('register.options');

                Route::post('/register', [WebAuthnController::class, 'register'])
                    ->middleware('throttle:vaultic.passkeys')
                    ->name('register.store');
            });

        Route::post('/authenticate/options', [WebAuthnController::class, 'authenticationOptions'])
            ->middleware('throttle:vaultic.passkeys')
            ->name('authenticate.options');

        Route::post('/authenticate', [WebAuthnController::class, 'authenticate'])
            ->middleware('throttle:vaultic.passkeys')
            ->name('authenticate.store');
    });
