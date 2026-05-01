# Vaultic v3.3

Vaultic is a Laravel package for WebAuthn/Passkeys (FIDO2) with Redis-backed challenge storage and fallback authentication flows.

This v3.3 release is optimized for Laravel 12 projects.

## Compatibility

- PHP `^8.1`
- Laravel `12.x`

## Architecture

Vaultic v1.0 uses a layered architecture to keep framework glue and business logic separated:

- HTTP Layer: controllers + middleware
- Service Layer: WebAuthn orchestration and fallback decisions
- Repository Layer: passkey persistence abstraction
- Contracts Layer: interfaces for verifier, service, and repository

Flow:

- Controller -> Service -> Repository -> Eloquent model

## Installation

```bash
composer require hamzi/vaultic:^3.3
```

Publish package assets:

```bash
php artisan vendor:publish --provider="Hamzi\\Vaultic\\VaulticServiceProvider" --tag=vaultic-config
php artisan vendor:publish --provider="Hamzi\\Vaultic\\VaulticServiceProvider" --tag=vaultic-migrations
php artisan vendor:publish --provider="Hamzi\\Vaultic\\VaulticServiceProvider" --tag=vaultic-views
php artisan migrate
```

For Laravel 5.5 package discovery is supported. For older installation preferences, manual registration is also valid:

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

VAULTIC_USER_MODEL=App\\User
VAULTIC_USER_IDENTIFIER_COLUMN=email

VAULTIC_RATE_LIMIT_ATTEMPTS=10
VAULTIC_RATE_LIMIT_DECAY_MINUTES=1

VAULTIC_FALLBACK_DRIVER=password
```

## Routes

Default route prefix is `/passkeys` with name prefix `vaultic.`.

- `POST /passkeys/register/options` -> `vaultic.register.options`
- `POST /passkeys/register` -> `vaultic.register.store`
- `POST /passkeys/authenticate/options` -> `vaultic.authenticate.options`
- `POST /passkeys/authenticate` -> `vaultic.authenticate.store`

Rate limiting uses named limiter middleware: `throttle:vaultic.passkeys`.

## WebAuthn Verifier Contract

Vaultic does not force a specific FIDO2 vendor package. Bind your own verifier implementation:

```php
$this->app->bind(
    Hamzi\Vaultic\Contracts\WebAuthnVerifier::class,
    App\Security\MyWebAuthnVerifier::class
);
```

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

## License

MIT
