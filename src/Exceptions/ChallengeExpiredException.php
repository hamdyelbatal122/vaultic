<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Exceptions;

/**
 * Thrown when a WebAuthn challenge has expired or was already consumed.
 */
class ChallengeExpiredException extends VaulticException
{
    /** @var string */
    protected $message = 'The WebAuthn challenge has expired or was already consumed.';
}
