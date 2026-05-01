<?php

namespace Hamzi\Vaultic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Hamzi\Vaultic\Models\Passkey;

interface WebAuthnService
{
    /**
     * @param mixed $user
     * @return array<string, mixed>
     */
    public function buildRegistrationOptions($user, $guardName = null);

    /**
     * @param mixed $user
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function registerPasskey($user, array $payload, $guardName = null);

    /**
     * @param string|null $identifier
     * @return array<string, mixed>
     */
    public function buildAuthenticationOptions($identifier, $guardName = null);

    /**
     * @param string|null $identifier
     * @param array<string, mixed> $payload
      * @param string|null $clientIp
     * @return array<string, mixed>
     */
     public function authenticate($identifier, array $payload, $guardName = null, $stateful = null, $clientIp = null);

    /**
     * @param Authenticatable $user
     * @param Passkey $passkey
     * @return bool
     */
    public function deletePasskey(Authenticatable $user, Passkey $passkey);
}
