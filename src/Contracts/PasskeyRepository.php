<?php

namespace Hamzi\Vaultic\Contracts;

use Hamzi\Vaultic\Models\Passkey;

interface PasskeyRepository
{
    /**
     * @param mixed $userId
     * @return array<int, array<string, string>>
     */
    public function listCredentialDescriptorsForUser($userId);

    /**
     * @param string $credentialId
     * @return Passkey|null
     */
    public function findByCredentialId($credentialId);

    /**
     * @param string $credentialId
     * @return bool
     */
    public function credentialExists($credentialId);

    /**
     * @param mixed $userId
     * @param array<string, mixed> $attributes
     * @return Passkey
     */
    public function createForUser($userId, array $attributes);

    /**
     * @param Passkey $passkey
     * @param int $signCount
     * @return void
     */
    public function markAsUsed(Passkey $passkey, $signCount);
}
