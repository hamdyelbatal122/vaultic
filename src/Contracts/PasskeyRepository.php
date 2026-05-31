<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Hamzi\Vaultic\Models\Passkey;

/**
 * Contract for passkey persistence operations.
 */
interface PasskeyRepository
{
    /**
     * List WebAuthn credential descriptors for the given authenticatable.
     *
     * @param  Authenticatable  $authenticatable
     * @return array<int, array<string, string>>
     */
    public function listCredentialDescriptorsForAuthenticatable(Authenticatable $authenticatable): array;

    /**
     * Find a passkey by its credential ID.
     *
     * @param  string  $credentialId
     * @return Passkey|null
     */
    public function findByCredentialId(string $credentialId): ?Passkey;

    /**
     * Check whether a credential ID already exists.
     *
     * @param  string  $credentialId
     * @return bool
     */
    public function credentialExists(string $credentialId): bool;

    /**
     * Create a new passkey for the given authenticatable.
     *
     * @param  Authenticatable       $authenticatable
     * @param  array<string, mixed>  $attributes
     * @return Passkey
     */
    public function createForAuthenticatable(Authenticatable $authenticatable, array $attributes): Passkey;

    /**
     * List all passkeys belonging to the given authenticatable.
     *
     * @param  Authenticatable  $authenticatable
     * @return Collection<int, Passkey>
     */
    public function listForAuthenticatable(Authenticatable $authenticatable): Collection;

    /**
     * Delete a passkey only if it belongs to the given authenticatable.
     *
     * @param  Authenticatable  $authenticatable
     * @param  Passkey          $passkey
     * @return bool
     */
    public function deleteForAuthenticatable(Authenticatable $authenticatable, Passkey $passkey): bool;

    /**
     * Rename a passkey only if it belongs to the given authenticatable.
     *
     * @param  Authenticatable  $authenticatable
     * @param  Passkey          $passkey
     * @param  string           $name
     * @return bool
     */
    public function renameForAuthenticatable(Authenticatable $authenticatable, Passkey $passkey, string $name): bool;

    /**
     * Update usage metadata after a successful authentication.
     *
     * @param  Passkey       $passkey
     * @param  int           $signCount
     * @param  string|null   $ipAddress
     * @return void
     */
    public function markAsUsed(Passkey $passkey, int $signCount, ?string $ipAddress = null): void;
}
