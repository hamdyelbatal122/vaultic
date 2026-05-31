<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Hamzi\Vaultic\Contracts\ApiTokenIssuer;

/**
 * Null-object implementation of ApiTokenIssuer.
 *
 * Returns an empty array when no token issuer is configured,
 * allowing stateless guards to operate without a token provider.
 */
class NullApiTokenIssuer implements ApiTokenIssuer
{
    /**
     * {@inheritDoc}
     */
    public function issueToken(Authenticatable $authenticatable, string $guardName, array $payload = []): array
    {
        return [];
    }
}
