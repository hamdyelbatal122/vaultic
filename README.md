# Vaultic

`Vaultic` is a production-ready Laravel package for **WebAuthn / Passkeys (FIDO2)** authentication with Redis-backed challenge storage, multi-device support, and an event-driven API.

## Why Vaultic

- **Multi-device**: each user can register multiple passkeys (phone, laptop, security key).
- **High performance**: challenges are stored in Redis and are **single-use**.
- **Safer auth**: validates the passkey belongs to the requested user identifier (prevents account confusion).
- **Extensible**: fires events for observability and integrations.
- **UI-ready**: Tailwind-styled Blade views + optional Livewire components.
- **Route protection**: middleware to enforce passkey-only access on sensitive routes.

## Requirements

- PHP `^8.2`
- Laravel `^11|^12`
- Redis recommended (for cache + rate limiting)

## Installation

```bash
composer require laravel/vaultic
```

Publish assets:

```bash
php artisan vendor:publish --tag=vaultic-config
php artisan vendor:publish --tag=vaultic-migrations
php artisan vendor:publish --tag=vaultic-views
php artisan migrate
```

## Configuration

Vaultic ships with `config/vaultic.php`.

Minimal recommended `.env`:

```env
VAULTIC_CACHE_STORE=redis
VAULTIC_CACHE_PREFIX=vaultic:challenge:
VAULTIC_CACHE_TTL=300

VAULTIC_RP_ID=example.com
VAULTIC_RP_NAME="My App"

VAULTIC_USER_MODEL=App\\Models\\User
VAULTIC_USER_IDENTIFIER_COLUMN=email

VAULTIC_FALLBACK_DRIVER=password
```

Key config fields:

- `vaultic.rp.id` / `vaultic.rp.name`: your relying party configuration.
- `vaultic.cache.*`: Redis cache store + TTL for challenges.
- `vaultic.routes.*`: route prefix, middleware, and route name prefix.
- `vaultic.fallback.driver`: `password` or `magic_link`.
- `vaultic.user_verification`: `required|preferred|discouraged` (forwarded to client options).

## Database

Vaultic publishes a migration that creates `passkeys`:

- `user_id` (FK)
- `credential_id` (unique)
- `public_key` (stored; hidden on model serialization)
- `sign_count` + `last_used_at` (replay/cloning detection support)
- `name` (device label)
- `transports`, `aaguid`

## Routes (API)

By default, routes are mounted under `/passkeys` and named with the `vaultic.` prefix.

Registration (requires authenticated user):

- `POST /passkeys/register/options` → `vaultic.register.options`
- `POST /passkeys/register` → `vaultic.register.store`

Authentication:

- `POST /passkeys/authenticate/options` → `vaultic.authenticate.options`
- `POST /passkeys/authenticate` → `vaultic.authenticate.store`

All endpoints are rate-limited via `throttle:vaultic.passkeys`.

## Authentication Flow

1. Client requests options (`/authenticate/options`) with `identifier` (e.g. email).
2. Vaultic issues a Redis challenge and returns WebAuthn option payload.
3. Client performs WebAuthn `navigator.credentials.get(...)`.
4. Client posts the assertion to `/authenticate`.
5. Vaultic verifies the assertion (via your verifier), loads the passkey, ensures it belongs to the user identified by `identifier`, then logs in the user.

## WebAuthn Verifier (FIDO2 integration)

Vaultic does not hard-couple to one WebAuthn library. You bind your own implementation:

```php
$this->app->bind(
    \Hamzi\Vaultic\Contracts\WebAuthnVerifier::class,
    \App\Security\MyWebAuthnVerifier::class
);
```

Your implementation must validate:

- RP ID + origin
- challenge
- signature
- user presence/verification policy
- attestation policy (if you use it)

## Blade & Livewire Components

Blade views (publishable):

- `vaultic::components.register`
- `vaultic::components.login`

Livewire components (only registered if Livewire is installed):

- `<livewire:vaultic-passkey-register />`
- `<livewire:vaultic-passkey-login />`

## Middleware (Passkey-only Routes)

Use the middleware alias `passkey.required` to enforce passkey authentication:

```php
Route::middleware(['auth', 'passkey.required'])->group(function () {
    Route::get('/settings/security', fn () => 'ok');
});
```

Vaultic stores the session flag at `vaultic.passkeys.authenticated`.

## Events

Listen for:

- `\Laravel\Vaultic\Events\PasskeyRegistered`
- `\Hamzi\Vaultic\Events\PasskeyAuthenticated`
- `\Hamzi\Vaultic\Events\AuthenticationFailed`

Example:

```php
Event::listen(\Hamzi\Vaultic\Events\AuthenticationFailed::class, function ($event) {
    // send to Sentry, logs, metrics, etc...
});
```

## Security Notes

- Challenges are **single-use** and removed from cache on `pull()`.
- Challenge keys hash the subject (`sha256`) to reduce PII leakage in Redis.
- Authentication ensures the credential belongs to the requested identifier.
- A decreasing `sign_count` returns `409` (possible cloned authenticator).
- Use HTTPS in production (required by WebAuthn on most platforms).

## Testing

```bash
composer test
```

Included:
- unit tests for Redis-like challenge single-use behavior
- feature tests for middleware + route registration

## License

MIT
