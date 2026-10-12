<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Concerns;

use Hamzi\Vaultic\Models\Passkey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds passkey relationship and convenience methods to an authenticatable model.
 *
 * @mixin Model
 */
trait HasPasskeys
{
    /**
     * Get all passkeys belonging to this authenticatable.
     *
     * @return MorphMany<Passkey, $this>
     */
    public function passkeys(): MorphMany
    {
        return $this->morphMany(Passkey::class, 'authenticatable');
    }

    /**
     * Determine whether this authenticatable has any registered passkeys.
     */
    public function hasPasskeys(): bool
    {
        return $this->passkeys()->exists();
    }

    /**
     * Determine whether this authenticatable has a passkey with the given credential ID.
     */
    public function hasPasskey(string $credentialId): bool
    {
        return $this->passkeys()->where('credential_id', $credentialId)->exists();
    }

    /**
     * Find a passkey belonging to this authenticatable by credential ID.
     */
    public function findPasskey(string $credentialId): ?Passkey
    {
        return $this->passkeys()->where('credential_id', $credentialId)->first();
    }

    /**
     * Delete a passkey belonging to this authenticatable by credential ID.
     */
    public function deletePasskey(string $credentialId): bool
    {
        return (bool) $this->passkeys()->where('credential_id', $credentialId)->delete();
    }

    /**
     * Get the total number of registered passkeys.
     */
    public function passkeyCount(): int
    {
        return $this->passkeys()->count();
    }

    /**
     * Get the most recently used passkey, or the most recently created one.
     */
    public function latestPasskey(): ?Passkey
    {
        return $this->passkeys()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->first();
    }
}
