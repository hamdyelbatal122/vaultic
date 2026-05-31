<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Contracts;

use Hamzi\Vaultic\Data\AssertionResult;
use Hamzi\Vaultic\Data\RegistrationResult;

/**
 * Contract for verifying WebAuthn registration and assertion payloads.
 *
 * Bind your own FIDO2 implementation to this interface in a service provider.
 */
interface WebAuthnVerifier
{
    /**
     * Verify a WebAuthn registration (attestation) payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  string                $challenge
     * @param  string                $rpId
     * @return RegistrationResult
     */
    public function verifyRegistration(array $payload, string $challenge, string $rpId): RegistrationResult;

    /**
     * Verify a WebAuthn authentication (assertion) payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  string                $challenge
     * @param  string                $rpId
     * @return AssertionResult
     */
    public function verifyAssertion(array $payload, string $challenge, string $rpId): AssertionResult;
}
