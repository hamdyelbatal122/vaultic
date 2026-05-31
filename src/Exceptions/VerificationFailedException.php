<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Exceptions;

/**
 * Thrown when WebAuthn registration or assertion verification fails.
 */
class VerificationFailedException extends VaulticException
{
    /** @var string */
    protected $message = 'WebAuthn verification failed.';
}
