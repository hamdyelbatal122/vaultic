<?php

declare(strict_types=1);

namespace Hamzi\Vaultic;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
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

/**
 * Service provider for the Vaultic WebAuthn/Passkeys package.
 *
 * Registers bindings, publishes assets, loads routes/views/migrations,
 * and configures rate limiting and Blade directives.
 */
class VaulticServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vaultic.php', 'vaultic');

        $this->app->singleton(ChallengeStore::class, function ($app) {
            $store = config('vaultic.cache.store');
            $cacheRepository = is_string($store) && $store !== ''
                ? $app['cache']->store($store)
                : $app['cache']->store();

            return new ChallengeStore(
                $cacheRepository,
                (string) config('vaultic.cache.prefix', 'vaultic:challenge:'),
                (int) config('vaultic.cache.ttl', 300),
            );
        });

        $this->app->bind(PasskeyRepository::class, EloquentPasskeyRepository::class);
        $this->app->bind(WebAuthnServiceContract::class, WebAuthnService::class);

        $this->app->bind(ApiTokenIssuer::class, NullApiTokenIssuer::class);
        $this->app->bind(WebAuthnVerifier::class, NullWebAuthnVerifier::class);
    }

    /**
     * Boot package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerMiddleware();
        $this->registerBladeDirectives();
        $this->registerRateLimiter();
    }

    /**
     * Register publishable assets.
     */
    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/vaultic.php' => config_path('vaultic.php'),
        ], 'vaultic-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'vaultic-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views/' => resource_path('views/vendor/vaultic'),
        ], 'vaultic-views');
    }

    /**
     * Load package routes.
     */
    private function registerRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    /**
     * Load and register views and Blade component namespace.
     */
    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vaultic');
        Blade::anonymousComponentNamespace('vaultic::components', 'vaultic');
    }

    /**
     * Load package migrations.
     */
    private function registerMigrations(): void
    {
        if (method_exists($this, 'loadMigrationsFrom')) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Register the passkey-required middleware alias.
     */
    private function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('passkey.required', RequirePasskey::class);
    }

    /**
     * Register Blade directives for passkey UI primitives.
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive('passkeyButton', function (string $expression): string {
            $data = $expression ?: '[]';

            return "<?php echo vaultic_passkey_button({$data}); ?>";
        });

        Blade::directive('passkeyPanel', function (string $expression): string {
            $data = $expression ?: '[]';

            return "<?php echo vaultic_passkey_panel({$data}); ?>";
        });
    }

    /**
     * Register the named rate limiter for passkey endpoints.
     */
    private function registerRateLimiter(): void
    {
        $limiter = $this->app->bound('cache.rateLimiter')
            ? $this->app->make('cache.rateLimiter')
            : null;

        if (! $limiter instanceof CacheRateLimiter || ! method_exists($limiter, 'for')) {
            return;
        }

        RateLimiter::for('vaultic.passkeys', function (Request $request): Limit {
            $attempts = (int) config('vaultic.rate_limit.attempts', 10);
            $decaySeconds = (int) config(
                'vaultic.rate_limit.decay_seconds',
                (int) config('vaultic.rate_limit.decay_minutes', 1) * 60,
            );
            $decayMinutes = max(1, (int) ceil($decaySeconds / 60));

            $rateLimitKey = hash('sha256', implode('|', array_filter([
                (string) optional($request->route())->getName(),
                (string) $request->ip(),
                (string) $request->input('identifier', ''),
                (string) $request->input('id', ''),
                (string) $request->input('challenge_key', ''),
            ], fn (string $value): bool => $value !== '')));

            return Limit::perMinutes($decayMinutes, $attempts)
                ->by($rateLimitKey);
        });
    }
}
