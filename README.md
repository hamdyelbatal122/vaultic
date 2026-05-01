# Vaultic v4.0

Vaultic is a Laravel package for WebAuthn/Passkeys (FIDO2) with challenge storage, fallback authentication flows, multi-guard support, and web/API-ready response handling.

This v4.0 release is optimized for Laravel 13 projects and modern PHP 8.3+ runtimes.

## Compatibility

- PHP `^8.3`
- Laravel `13.x`

## Version Matrix

| Vaultic Tag | PHP | Laravel |
| --- | --- | --- |
| v1.0.2 | 7.1.3+ | 5.5-5.8 |
| v1.2.1 | 7.2.5+ | 6.x |
| v1.3.0 | 7.2.5+ | 7.x |
| v2.0.0 | 7.3+ | 8.x |
| v3.0.1 | 8.0.2+ | 9.x |
| v3.1.0 | 8.1+ | 10.x |
| v3.2.1 | 8.2+ | 11.x |
| v3.3.1 | 8.2+ | 12.x |
| v4.0.0 | 8.3+ | 13.x |

## Architecture

Vaultic uses a layered architecture to keep framework glue and business logic separated:

- HTTP Layer: controllers + middleware
- Service Layer: WebAuthn orchestration and fallback decisions
- Repository Layer: passkey persistence abstraction
- Contracts Layer: interfaces for verifier, token issuing, service, and repository

Flow:

- Controller -> Service -> Repository -> Eloquent model

## Highlights

- Multi-guard authentication with per-guard model and identifier resolution
- Stateful web flows and stateless API flows from the same package endpoints
- UI-agnostic JSON endpoints that work with Blade, Livewire, Inertia, Vue, React, or native mobile clients
- Polymorphic passkey ownership, so passkeys can belong to different authenticatable models
- Optional token issuing abstraction for API guards, including a built-in Sanctum-friendly issuer

## Installation

```bash
composer require hamzi/vaultic
```

Publish package assets:

```bash
php artisan vendor:publish --provider="Hamzi\\Vaultic\\VaulticServiceProvider" --tag=vaultic-config
php artisan vendor:publish --provider="Hamzi\\Vaultic\\VaulticServiceProvider" --tag=vaultic-migrations
php artisan vendor:publish --provider="Hamzi\\Vaultic\\VaulticServiceProvider" --tag=vaultic-views
php artisan migrate
```

Laravel package discovery is enabled by default. Manual registration remains available when needed:

```php
// config/app.php
'providers' => [
    Hamzi\Vaultic\VaulticServiceProvider::class,
],
```

## Configuration

Vaultic ships with [config/vaultic.php](config/vaultic.php).

Minimal `.env`:

```env
VAULTIC_CACHE_STORE=redis
VAULTIC_CACHE_PREFIX=vaultic:challenge:
VAULTIC_CACHE_TTL=300

VAULTIC_RP_ID=example.com
VAULTIC_RP_NAME="My App"

VAULTIC_USER_MODEL=App\\Models\\User
VAULTIC_USER_IDENTIFIER_COLUMN=email
VAULTIC_DEFAULT_GUARD=web

VAULTIC_WEB_GUARD=web
VAULTIC_WEB_AUTH_MIDDLEWARE=auth:web

VAULTIC_API_GUARD=api
VAULTIC_API_AUTH_MIDDLEWARE=auth:api
VAULTIC_API_TOKEN_ISSUER=Hamzi\\Vaultic\\Services\\SanctumApiTokenIssuer

VAULTIC_RATE_LIMIT_ATTEMPTS=10
VAULTIC_RATE_LIMIT_DECAY_MINUTES=1

VAULTIC_FALLBACK_DRIVER=password
```

Guard configuration lives under `auth.guards` in [config/vaultic.php](config/vaultic.php). Each guard can define:

- `provider_model`
- `identifier_column`
- `stateful`
- `remember`
- `token_issuer`

## Routes

Vaultic exposes two channels by default:

- Web routes under `/passkeys` with name prefix `vaultic.`
- API routes under `/api/passkeys` with name prefix `vaultic.api.`

- `POST /passkeys/register/options` -> `vaultic.register.options`
- `POST /passkeys/register` -> `vaultic.register.store`
- `POST /passkeys/authenticate/options` -> `vaultic.authenticate.options`
- `POST /passkeys/authenticate` -> `vaultic.authenticate.store`
- `POST /api/passkeys/register/options` -> `vaultic.api.register.options`
- `POST /api/passkeys/register` -> `vaultic.api.register.store`
- `POST /api/passkeys/authenticate/options` -> `vaultic.api.authenticate.options`
- `POST /api/passkeys/authenticate` -> `vaultic.api.authenticate.store`

Rate limiting uses named limiter middleware: `throttle:vaultic.passkeys`.

All endpoints return JSON, so the package is not tied to any UI stack.

## WebAuthn Verifier Contract

Vaultic does not force a specific FIDO2 vendor package. Bind your own verifier implementation:

```php
$this->app->bind(
    Hamzi\Vaultic\Contracts\WebAuthnVerifier::class,
    App\Security\MyWebAuthnVerifier::class
);
```

If no verifier is bound, Vaultic throws a clear runtime exception instead of silently failing.

## API Token Issuing

For stateless guards, Vaultic can return token payloads after a successful passkey assertion.

Bind your own issuer:

```php
$this->app->bind(
    Hamzi\Vaultic\Contracts\ApiTokenIssuer::class,
    App\Auth\IssueVaulticToken::class
);
```

Or use the included Sanctum-oriented issuer when your authenticatable model exposes `createToken()`:

```php
'auth' => [
    'guards' => [
        'api' => [
            'guard' => 'sanctum',
            'provider_model' => App\Models\User::class,
            'identifier_column' => 'email',
            'stateful' => false,
            'token_issuer' => Hamzi\Vaultic\Services\SanctumApiTokenIssuer::class,
        ],
    ],
],
```

Successful API authentication responses include a `tokens` array in the JSON payload.

## Frontend Integration

Vaultic only owns the backend WebAuthn flow. You can pair it with any client that can call the JSON endpoints and forward WebAuthn browser payloads.

- Blade or Livewire forms
- Inertia or SPA frontends
- Native mobile or hybrid clients through API routes
- Admin panels with separate guards and authenticatable models

## Middleware

Use `passkey.required` for routes that must have a passkey-authenticated session.

```php
Route::middleware(['auth', 'passkey.required'])->group(function () {
    Route::get('/settings/security', function () {
        return 'ok';
    });
});
```

## Events

- `Hamzi\Vaultic\Events\PasskeyRegistered`
- `Hamzi\Vaultic\Events\PasskeyAuthenticated`
- `Hamzi\Vaultic\Events\AuthenticationFailed`

## Tests

```bash
composer test
```

Includes:

- unit tests for challenge issuance/pull behavior
- feature tests for route registration and middleware behavior
- service tests for multi-guard resolution, stateful login flow, and stateless token payloads

## Repository Standards

- License: [LICENSE](LICENSE)
- Contribution Guide: [CONTRIBUTING.md](CONTRIBUTING.md)
- Security Policy: [SECURITY.md](SECURITY.md)
- Code of Conduct: [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- Changelog: [CHANGELOG.md](CHANGELOG.md)

## License

vaultic is open-sourced software licensed under the MIT license. See [LICENSE](LICENSE) for more details.
