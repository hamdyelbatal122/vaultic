<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Contracts;

use Hamzi\Vaultic\Models\Passkey;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Contract for passkey persistence operations.
 */
interface PasskeyRepository
{
    /**
     * List WebAuthn credential descriptors for the given authenticatable.
     *
     * @return array<int, array<string, string>>
     */
    public function listCredentialDescriptorsForAuthenticatable(Authenticatable $authenticatable): array;

    /**
     * Find a passkey by its credential ID.
     */
    public function findByCredentialId(string $credentialId): ?Passkey;

    /**
     * Check whether a credential ID already exists.
     */
    public function credentialExists(string $credentialId): bool;

    /**
     * Create a new passkey for the given authenticatable.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createForAuthenticatable(Authenticatable $authenticatable, array $attributes): Passkey;

    /**
     * List all passkeys belonging to the given authenticatable.
     *
     * @return Collection<int, Passkey>
     */
    public function listForAuthenticatable(Authenticatable $authenticatable): Collection;

    /**
     * Delete a passkey only if it belongs to the given authenticatable.
     */
    public function deleteForAuthenticatable(Authenticatable $authenticatable, Passkey $passkey): bool;

    /**
     * Rename a passkey only if it belongs to the given authenticatable.
     */
    public function renameForAuthenticatable(Authenticatable $authenticatable, Passkey $passkey, string $name): bool;

    /**
     * Update usage metadata after a successful authentication.
     */
    public function markAsUsed(Passkey $passkey, int $signCount, ?string $ipAddress = null): void;
}
