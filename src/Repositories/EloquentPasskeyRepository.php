<?php

namespace Hamzi\Vaultic\Repositories;

use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Hamzi\Vaultic\Models\Passkey;

class EloquentPasskeyRepository implements PasskeyRepository
{
    /**
     * @param mixed $userId
     * @return array<int, array<string, string>>
     */
    public function listCredentialDescriptorsForUser($userId)
    {
        $descriptors = [];

        $passkeys = Passkey::query()->where('user_id', $userId)->get(['credential_id']);

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
     * @param mixed $userId
     * @param array<string, mixed> $attributes
     * @return Passkey
     */
    public function createForUser($userId, array $attributes)
    {
        $attributes['user_id'] = $userId;

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
        $passkey->last_used_at = date('Y-m-d H:i:s');
        $passkey->save();
    }
}
