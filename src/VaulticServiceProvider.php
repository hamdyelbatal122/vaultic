<?php

namespace Hamzi\Vaultic;

use Illuminate\Support\ServiceProvider;
use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Hamzi\Vaultic\Contracts\WebAuthnService as WebAuthnServiceContract;
use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Http\Middleware\RequirePasskey;
use Hamzi\Vaultic\Repositories\EloquentPasskeyRepository;
use Hamzi\Vaultic\Services\ChallengeStore;
use Hamzi\Vaultic\Services\NullWebAuthnVerifier;
use Hamzi\Vaultic\Services\WebAuthnService;

class VaulticServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vaultic.php', 'vaultic');

        $this->app->singleton(ChallengeStore::class, function ($app) {
            return new ChallengeStore(
                $app['cache']->store(config('vaultic.cache.store', 'redis')),
                (string) config('vaultic.cache.prefix', 'vaultic:challenge:'),
                (int) config('vaultic.cache.ttl', 300)
            );
        });

        $this->app->bind(PasskeyRepository::class, EloquentPasskeyRepository::class);
        $this->app->bind(WebAuthnServiceContract::class, WebAuthnService::class);

        $this->app->bind(WebAuthnVerifier::class, NullWebAuthnVerifier::class);
    }

    public function boot()
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vaultic');
        $this->app['router']->aliasMiddleware('passkey.required', RequirePasskey::class);

        if (method_exists($this, 'loadMigrationsFrom')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
