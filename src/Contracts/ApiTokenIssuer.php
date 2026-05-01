<?php

namespace Hamzi\Vaultic\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ApiTokenIssuer
{
    /**
     * @param Authenticatable $authenticatable
     * @param string $guardName
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function issueToken(Authenticatable $authenticatable, $guardName, array $payload = []);
}
