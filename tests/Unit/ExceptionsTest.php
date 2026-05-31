<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Unit;

use Hamzi\Vaultic\Exceptions\VaulticException;
use Hamzi\Vaultic\Exceptions\ChallengeExpiredException;
use Hamzi\Vaultic\Exceptions\CredentialAlreadyRegisteredException;
use Hamzi\Vaultic\Exceptions\VerificationFailedException;
use Hamzi\Vaultic\Exceptions\ClonedAuthenticatorException;
use Hamzi\Vaultic\Tests\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_exceptions_inherit_from_vaultic_exception(): void
    {
        $challengeExpired = new ChallengeExpiredException('Expired');
        $credentialRegistered = new CredentialAlreadyRegisteredException('Registered');
        $verificationFailed = new VerificationFailedException('Failed');
        $clonedAuthenticator = new ClonedAuthenticatorException('Cloned');

        $this->assertInstanceOf(VaulticException::class, $challengeExpired);
        $this->assertInstanceOf(VaulticException::class, $credentialRegistered);
        $this->assertInstanceOf(VaulticException::class, $verificationFailed);
        $this->assertInstanceOf(VaulticException::class, $clonedAuthenticator);

        $this->assertInstanceOf(\RuntimeException::class, $challengeExpired);
    }
}
