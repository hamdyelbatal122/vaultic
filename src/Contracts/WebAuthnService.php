<?php

namespace Hamzi\Vaultic\Contracts;

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
     * @param string $identifier
     * @return array<string, mixed>
     */
    public function buildAuthenticationOptions($identifier, $guardName = null);

    /**
     * @param string $identifier
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function authenticate($identifier, array $payload, $guardName = null, $stateful = null);
}
