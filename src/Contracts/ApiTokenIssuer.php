<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contract for issuing API tokens after successful passkey authentication.
 */
interface ApiTokenIssuer
{
    /**
     * Issue an API token for the given authenticatable after passkey assertion.
     *
     * @param  Authenticatable       $authenticatable
     * @param  string                $guardName
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function issueToken(Authenticatable $authenticatable, string $guardName, array $payload = []): array;
}
