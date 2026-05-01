<?php

namespace Hamzi\Vaultic\Contracts;

interface WebAuthnService
{
    /**
     * @param mixed $user
     * @return array<string, mixed>
     */
    public function buildRegistrationOptions($user);

    /**
     * @param mixed $user
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function registerPasskey($user, array $payload);

    /**
     * @param string $identifier
     * @return array<string, mixed>
     */
    public function buildAuthenticationOptions($identifier);

    /**
     * @param string $identifier
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function authenticate($identifier, array $payload);
}
