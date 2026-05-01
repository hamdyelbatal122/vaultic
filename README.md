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



## Configuration

Vaultic ships with [config/vaultic.php](config/vaultic.php).

'Vaultic no longer requires package-specific `.env` entries for the default setup.

By default, Vaultic derives the relying party from your main Laravel application settings (APP_URL, APP_NAME) and uses the default cache store configured in your app:

```php
// config/vaultic.php
'cache' => [
    'store' => config('cache.default', 'file'), // Uses your app's default cache store
    'prefix' => 'vaultic:challenge:',
    'ttl' => 300,
],

'auth' => [
    'default_guard' => 'web',
    'guards' => [
        'web' => [
            'guard' => 'web',
            'provider_model' => App\Models\User::class,
            'identifier_column' => 'email',
        ],
        'api' => [
            'guard' => 'sanctum',
            'provider_model' => App\Models\User::class,
            'identifier_column' => 'email',
            'token_issuer' => Hamzi\Vaultic\Services\SanctumApiTokenIssuer::class,
        ],
    ],
],

'rate_limit' => [
    'attempts' => 10,
    'decay_seconds' => 60,
],
'fallback' => [
    'driver' => 'password',
],
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
- `DELETE /passkeys/{passkey}` -> `vaultic.passkeys.destroy`
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

## Blade Integration

Vaultic now ships with publishable Tailwind-ready Blade primitives on top of the JSON endpoints, so you can use the package without building your own JavaScript WebAuthn bridge first.

### Passkey Login Button

Component usage:

```blade
<input id="email" type="email" name="email" autocomplete="username webauthn">

<x-vaultic::passkey-button
    identifier-selector="#email"
    class="w-full"
/>
```

Directive usage:

```blade
@passkeyButton(['identifierSelector' => '#email'])
```

Helper usage:

```blade
{{ vaultic_passkey_button(['identifierSelector' => '#email']) }}
```

### Passkey Management Panel

Render the Tailwind registration form and linked-passkeys table anywhere inside your authenticated Blade views:

```blade
<x-vaultic::passkey-panel />
```

Or through the directive/helper APIs:

```blade
@passkeyPanel()

{{ vaultic_passkey_panel() }}
```

Both primitives are customizable through props, route overrides, labels, and by publishing the package views with `vaultic-views`.

## Frontend Integration

If you prefer a custom client, Vaultic still exposes the raw JSON endpoints for:

- Blade pages with your own markup
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
