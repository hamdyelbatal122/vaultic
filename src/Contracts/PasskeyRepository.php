<?php

namespace Hamzi\Vaultic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

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
     * @param Authenticatable $authenticatable
     * @return Collection<int, Passkey>
     */
    public function listForAuthenticatable(Authenticatable $authenticatable): Collection;

    /**
     * @param Authenticatable $authenticatable
     * @param Passkey $passkey
     * @return bool
     */
    public function deleteForAuthenticatable(Authenticatable $authenticatable, Passkey $passkey): bool;

    /**
     * @param Passkey $passkey
     * @param int $signCount
     * @return void
     */
    public function markAsUsed(Passkey $passkey, $signCount);
}
