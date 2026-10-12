<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Contracts;

use Hamzi\Vaultic\Models\Passkey;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contract for the WebAuthn orchestration service.
 */
interface WebAuthnService
{
    /**
     * Build WebAuthn registration options for the given user.
     *
     * @return array<string, mixed>
     */
    public function buildRegistrationOptions(Authenticatable $user, ?string $guardName = null): array;

    /**
     * Verify and persist a new passkey registration.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function registerPasskey(Authenticatable $user, array $payload, ?string $guardName = null): array;

    /**
     * Build WebAuthn authentication options.
     *
     * @return array<string, mixed>
     */
    public function buildAuthenticationOptions(?string $identifier, ?string $guardName = null): array;

    /**
     * Verify a WebAuthn assertion and authenticate the user.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function authenticate(
        ?string $identifier,
        array $payload,
        ?string $guardName = null,
        ?bool $stateful = null,
        ?string $clientIp = null,
    ): array;

    /**
     * Delete a passkey belonging to the given user.
     */
    public function deletePasskey(Authenticatable $user, Passkey $passkey): bool;

    /**
     * Rename a passkey belonging to the given user.
     */
    public function renamePasskey(Authenticatable $user, Passkey $passkey, string $name): bool;
}
