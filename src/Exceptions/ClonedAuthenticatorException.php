<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Exceptions;

/**
 * Thrown when the sign counter regresses, indicating a potential cloned authenticator.
 */
class ClonedAuthenticatorException extends VaulticException
{
    /** @var string */
    protected $message = 'Potential cloned authenticator detected — sign counter regression.';
}
