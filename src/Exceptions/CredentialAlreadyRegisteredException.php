<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Exceptions;

/**
 * Thrown when a credential ID is already registered in the system.
 */
class CredentialAlreadyRegisteredException extends VaulticException
{
    /** @var string */
    protected $message = 'This passkey credential is already registered.';
}
