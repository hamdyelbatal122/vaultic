<?php

namespace Hamzi\Vaultic;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Hamzi\Vaultic\Contracts\ApiTokenIssuer;
use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Hamzi\Vaultic\Contracts\WebAuthnService as WebAuthnServiceContract;
use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Http\Middleware\RequirePasskey;
use Hamzi\Vaultic\Repositories\EloquentPasskeyRepository;
use Hamzi\Vaultic\Services\NullApiTokenIssuer;
use Hamzi\Vaultic\Services\ChallengeStore;
use Hamzi\Vaultic\Services\NullWebAuthnVerifier;
use Hamzi\Vaultic\Services\WebAuthnService;

class VaulticServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vaultic.php', 'vaultic');

        $this->app->singleton(ChallengeStore::class, function ($app) {
            $store = config('vaultic.cache.store');
            $cacheRepository = is_string($store) && $store !== ''
                ? $app['cache']->store($store)
                : $app['cache']->store();

            return new ChallengeStore(
                $cacheRepository,
                (string) config('vaultic.cache.prefix', 'vaultic:challenge:'),
                (int) config('vaultic.cache.ttl', 300)
            );
        });

        $this->app->bind(PasskeyRepository::class, EloquentPasskeyRepository::class);
        $this->app->bind(WebAuthnServiceContract::class, WebAuthnService::class);

        $this->app->bind(ApiTokenIssuer::class, NullApiTokenIssuer::class);
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

        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'vaultic');
        Blade::anonymousComponentNamespace('vaultic::components', 'vaultic');
        $this->registerBladeDirectives();
        $this->app['router']->aliasMiddleware('passkey.required', RequirePasskey::class);

        if (method_exists($this, 'loadMigrationsFrom')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->registerRateLimiter();
    }

    private function registerRateLimiter()
    {
        if (!class_exists(RateLimiter::class) || !method_exists(RateLimiter::class, 'for')) {
            return;
        }

        RateLimiter::for('vaultic.passkeys', function (Request $request) {
            $attempts = (int) config('vaultic.rate_limit.attempts', 10);
            $decaySeconds = (int) config(
                'vaultic.rate_limit.decay_seconds',
                (int) config('vaultic.rate_limit.decay_minutes', 1) * 60
            );
            $decayMinutes = max(1, (int) ceil($decaySeconds / 60));

            return Limit::perMinutes($decayMinutes, $attempts)
                ->by(implode('|', [
                    (string) optional($request->route())->getName(),
                    $request->ip(),
                    (string) $request->input('identifier', ''),
                ]));
        });
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive('passkeyButton', function ($expression) {
            $data = $expression ?: '[]';

            return "<?php echo vaultic_passkey_button({$data}); ?>";
        });

        Blade::directive('passkeyPanel', function ($expression) {
            $data = $expression ?: '[]';

            return "<?php echo vaultic_passkey_panel({$data}); ?>";
        });
    }
}
