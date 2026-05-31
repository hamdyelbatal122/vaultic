<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Services;

use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Data\AssertionResult;
use Hamzi\Vaultic\Data\RegistrationResult;
use Hamzi\Vaultic\Exceptions\VaulticException;

/**
 * Null-object implementation of WebAuthnVerifier.
 *
 * Throws a clear runtime exception if called, guiding developers to bind
 * a real FIDO2 verifier implementation before using the package.
 */
class NullWebAuthnVerifier implements WebAuthnVerifier
{
    /**
     * {@inheritDoc}
     */
    public function verifyRegistration(array $payload, string $challenge, string $rpId): RegistrationResult
    {
        throw $this->missingVerifierException();
    }

    /**
     * {@inheritDoc}
     */
    public function verifyAssertion(array $payload, string $challenge, string $rpId): AssertionResult
    {
        throw $this->missingVerifierException();
    }

    /**
     * Build a descriptive exception directing the developer to configure a verifier.
     */
    private function missingVerifierException(): VaulticException
    {
        return new VaulticException(
            'No WebAuthn verifier configured. Bind '
            . WebAuthnVerifier::class
            . ' to your FIDO2 implementation in a service provider before calling Vaultic endpoints.',
        );
    }
}
