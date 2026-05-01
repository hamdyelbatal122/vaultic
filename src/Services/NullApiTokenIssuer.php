<?php

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Hamzi\Vaultic\Contracts\ApiTokenIssuer;

class NullApiTokenIssuer implements ApiTokenIssuer
{
    /**
     * @param Authenticatable $authenticatable
     * @param string $guardName
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function issueToken(Authenticatable $authenticatable, $guardName, array $payload = [])
    {
        return [];
    }
}
