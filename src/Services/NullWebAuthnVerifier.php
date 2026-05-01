<?php

namespace Hamzi\Vaultic\Services;

use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Data\AssertionResult;
use Hamzi\Vaultic\Data\RegistrationResult;
use RuntimeException;

class NullWebAuthnVerifier implements WebAuthnVerifier
{
    public function verifyRegistration(array $payload, string $challenge, string $rpId): RegistrationResult
    {
        throw $this->missingVerifierException();
    }

    public function verifyAssertion(array $payload, string $challenge, string $rpId): AssertionResult
    {
        throw $this->missingVerifierException();
    }

    /**
     * @return RuntimeException
     */
    private function missingVerifierException()
    {
        return new RuntimeException(
            'No WebAuthn verifier configured. Bind '.WebAuthnVerifier::class.' to your FIDO2 implementation in a service provider before calling Vaultic endpoints.'
        );
    }
}
