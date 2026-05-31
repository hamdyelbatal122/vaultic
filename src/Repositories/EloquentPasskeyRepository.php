<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Hamzi\Vaultic\Models\Passkey;

/**
 * Eloquent-backed implementation of the passkey repository contract.
 */
class EloquentPasskeyRepository implements PasskeyRepository
{
    /**
     * {@inheritDoc}
     */
    public function listCredentialDescriptorsForAuthenticatable(Authenticatable $authenticatable): array
    {
        return Passkey::query()
            ->where('authenticatable_type', get_class($authenticatable))
            ->where('authenticatable_id', (string) $authenticatable->getAuthIdentifier())
            ->pluck('credential_id')
            ->map(fn (string $credentialId): array => [
                'type' => 'public-key',
                'id'   => $credentialId,
            ])
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findByCredentialId(string $credentialId): ?Passkey
    {
        return Passkey::query()->where('credential_id', $credentialId)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function credentialExists(string $credentialId): bool
    {
        return Passkey::query()->where('credential_id', $credentialId)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function createForAuthenticatable(Authenticatable $authenticatable, array $attributes): Passkey
    {
        $attributes['authenticatable_type'] = get_class($authenticatable);
        $attributes['authenticatable_id'] = (string) $authenticatable->getAuthIdentifier();

        return Passkey::query()->create($attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function listForAuthenticatable(Authenticatable $authenticatable): Collection
    {
        return Passkey::query()
            ->where('authenticatable_type', get_class($authenticatable))
            ->where('authenticatable_id', (string) $authenticatable->getAuthIdentifier())
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteForAuthenticatable(Authenticatable $authenticatable, Passkey $passkey): bool
    {
        if (! $this->belongsToAuthenticatable($authenticatable, $passkey)) {
            return false;
        }

        return (bool) $passkey->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function renameForAuthenticatable(Authenticatable $authenticatable, Passkey $passkey, string $name): bool
    {
        if (! $this->belongsToAuthenticatable($authenticatable, $passkey)) {
            return false;
        }

        $passkey->name = $name;
        $passkey->save();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function markAsUsed(Passkey $passkey, int $signCount, ?string $ipAddress = null): void
    {
        $passkey->sign_count = max((int) $passkey->sign_count, $signCount);
        $passkey->last_used_at = now();
        $passkey->last_used_ip = is_string($ipAddress) && filter_var(trim($ipAddress), FILTER_VALIDATE_IP)
            ? trim($ipAddress)
            : null;
        $passkey->save();
    }

    /**
     * Check whether the passkey belongs to the given authenticatable.
     */
    private function belongsToAuthenticatable(Authenticatable $authenticatable, Passkey $passkey): bool
    {
        return $passkey->authenticatable_type === get_class($authenticatable)
            && (string) $passkey->authenticatable_id === (string) $authenticatable->getAuthIdentifier();
    }
}
