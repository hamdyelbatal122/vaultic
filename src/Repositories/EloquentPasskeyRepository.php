<?php

namespace Hamzi\Vaultic\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;
use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Hamzi\Vaultic\Models\Passkey;

class EloquentPasskeyRepository implements PasskeyRepository
{
    /**
     * @param Authenticatable $authenticatable
     * @return array<int, array<string, string>>
     */
    public function listCredentialDescriptorsForAuthenticatable(Authenticatable $authenticatable)
    {
        $descriptors = [];

        $passkeys = Passkey::query()
            ->where('authenticatable_type', get_class($authenticatable))
            ->where('authenticatable_id', (string) $authenticatable->getAuthIdentifier())
            ->get(['credential_id']);

        foreach ($passkeys as $passkey) {
            $descriptors[] = [
                'type' => 'public-key',
                'id' => (string) $passkey->credential_id,
            ];
        }

        return $descriptors;
    }

    /**
     * @param string $credentialId
     * @return Passkey|null
     */
    public function findByCredentialId($credentialId)
    {
        return Passkey::query()->where('credential_id', $credentialId)->first();
    }

    /**
     * @param string $credentialId
     * @return bool
     */
    public function credentialExists($credentialId)
    {
        return Passkey::query()->where('credential_id', $credentialId)->exists();
    }

    /**
     * @param Authenticatable $authenticatable
     * @param array<string, mixed> $attributes
     * @return Passkey
     */
    public function createForAuthenticatable(Authenticatable $authenticatable, array $attributes)
    {
        $attributes['authenticatable_type'] = get_class($authenticatable);
        $attributes['authenticatable_id'] = (string) $authenticatable->getAuthIdentifier();

        return Passkey::query()->create($attributes);
    }

    /**
     * @param Passkey $passkey
     * @param int $signCount
     * @return void
     */
    public function markAsUsed(Passkey $passkey, $signCount)
    {
        $passkey->sign_count = max((int) $passkey->sign_count, (int) $signCount);
        $passkey->last_used_at = now();
        $passkey->save();
    }
}
