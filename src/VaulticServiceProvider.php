<?php

declare(strict_types=1);

namespace Hamzi\Vaultic;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Http\Middleware\RequirePasskey;
use Hamzi\Vaultic\Livewire\PasskeyLogin;
use Hamzi\Vaultic\Livewire\PasskeyRegister;
use Hamzi\Vaultic\Services\ChallengeStore;
use Hamzi\Vaultic\Services\NullWebAuthnVerifier;
use Livewire\Livewire;

final class VaulticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vaultic.php', 'vaultic');

        $this->app->singleton(ChallengeStore::class, static fn ($app): ChallengeStore => new ChallengeStore(
            cache: $app['cache']->store(config('vaultic.cache.store', 'redis')),
            prefix: (string) config('vaultic.cache.prefix', 'vaultic:challenge:'),
            ttlSeconds: (int) config('vaultic.cache.ttl', 300),
        ));

        $this->app->bind(WebAuthnVerifier::class, NullWebAuthnVerifier::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/vaultic.php' => config_path('vaultic.php'),
        ], 'vaultic-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'vaultic-migrations');

        $this->publishes([
            __DIR__.'/../resources/views/' => resource_path('views/vendor/vaultic'),
        ], 'vaultic-views');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vaultic');
        $this->app['router']->aliasMiddleware('passkey.required', RequirePasskey::class);

        $this->registerRateLimiter();
        $this->registerLivewireComponents();
    }

    private function registerRateLimiter(): void
    {
        RateLimiter::for('vaultic.passkeys', static function (Request $request): Limit {
            $maxAttempts = (int) config('vaultic.rate_limit.attempts', 10);
            $decaySeconds = (int) config('vaultic.rate_limit.decay_seconds', 60);
            $userIdentifier = (string) optional($request->user())->getAuthIdentifier();

            return Limit::perMinutes(max(1, (int) ceil($decaySeconds / 60)), $maxAttempts)
                ->by($request->ip().'|'.$userIdentifier.'|'.$request->input('identifier', ''))
                ->response(static fn () => response()->json([
                    'message' => 'Too many Vaultic authentication attempts.',
                    'retry_after_seconds' => $decaySeconds,
                ], 429));
        });
    }

    private function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component('vaultic-passkey-register', PasskeyRegister::class);
        Livewire::component('vaultic-passkey-login', PasskeyLogin::class);
    }
}
