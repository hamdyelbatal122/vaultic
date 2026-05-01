<?php

namespace Hamzi\Vaultic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

use Hamzi\Vaultic\Models\Passkey;

interface PasskeyRepository
{
    /**
     * @param Authenticatable $authenticatable
     * @return array<int, array<string, string>>
     */
    public function listCredentialDescriptorsForAuthenticatable(Authenticatable $authenticatable);

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
    * @param Authenticatable $authenticatable
     * @param array<string, mixed> $attributes
     * @return Passkey
     */
    public function createForAuthenticatable(Authenticatable $authenticatable, array $attributes);

    /**
     * @param Passkey $passkey
     * @param int $signCount
     * @return void
     */
    public function markAsUsed(Passkey $passkey, $signCount);
}
