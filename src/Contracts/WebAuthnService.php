<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Hamzi\Vaultic\Models\Passkey;

/**
 * Contract for the WebAuthn orchestration service.
 */
interface WebAuthnService
{
    /**
     * Build WebAuthn registration options for the given user.
     *
     * @param  Authenticatable       $user
     * @param  string|null           $guardName
     * @return array<string, mixed>
     */
    public function buildRegistrationOptions(Authenticatable $user, ?string $guardName = null): array;

    /**
     * Verify and persist a new passkey registration.
     *
     * @param  Authenticatable       $user
     * @param  array<string, mixed>  $payload
     * @param  string|null           $guardName
     * @return array<string, mixed>
     */
    public function registerPasskey(Authenticatable $user, array $payload, ?string $guardName = null): array;

    /**
     * Build WebAuthn authentication options.
     *
     * @param  string|null           $identifier
     * @param  string|null           $guardName
     * @return array<string, mixed>
     */
    public function buildAuthenticationOptions(?string $identifier, ?string $guardName = null): array;

    /**
     * Verify a WebAuthn assertion and authenticate the user.
     *
     * @param  string|null           $identifier
     * @param  array<string, mixed>  $payload
     * @param  string|null           $guardName
     * @param  bool|null             $stateful
     * @param  string|null           $clientIp
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
     *
     * @param  Authenticatable  $user
     * @param  Passkey          $passkey
     * @return bool
     */
    public function deletePasskey(Authenticatable $user, Passkey $passkey): bool;

    /**
     * Rename a passkey belonging to the given user.
     *
     * @param  Authenticatable  $user
     * @param  Passkey          $passkey
     * @param  string           $name
     * @return bool
     */
    public function renamePasskey(Authenticatable $user, Passkey $passkey, string $name): bool;
}
