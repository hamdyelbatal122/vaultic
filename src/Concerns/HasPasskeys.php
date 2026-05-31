<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Hamzi\Vaultic\Models\Passkey;

/**
 * Adds passkey relationship and convenience methods to an authenticatable model.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPasskeys
{
    /**
     * Get all passkeys belonging to this authenticatable.
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
