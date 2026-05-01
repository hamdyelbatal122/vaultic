<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Contracts;

use Hamzi\Vaultic\Data\AssertionResult;
use Hamzi\Vaultic\Data\RegistrationResult;

interface WebAuthnVerifier
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyRegistration(array $payload, string $challenge, string $rpId): RegistrationResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyAssertion(array $payload, string $challenge, string $rpId): AssertionResult;
}
