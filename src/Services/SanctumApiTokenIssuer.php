<?php

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;
use Hamzi\Vaultic\Contracts\ApiTokenIssuer;

class SanctumApiTokenIssuer implements ApiTokenIssuer
{
    /**
     * @param Authenticatable $authenticatable
     * @param string $guardName
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function issueToken(Authenticatable $authenticatable, $guardName, array $payload = [])
    {
        if (! method_exists($authenticatable, 'createToken')) {
            throw new RuntimeException(
                'The authenticatable model must expose createToken() to use '.self::class.'. Install Laravel Sanctum and add HasApiTokens to the model.'
            );
        }

        $tokenName = isset($payload['token_name']) && is_string($payload['token_name']) && $payload['token_name'] !== ''
            ? $payload['token_name']
            : 'vaultic-passkey';

        $abilities = isset($payload['abilities']) && is_array($payload['abilities'])
            ? array_values($payload['abilities'])
            : ['*'];

        $token = $authenticatable->createToken($tokenName, $abilities);

        return [
            'access_token' => property_exists($token, 'plainTextToken') ? $token->plainTextToken : null,
            'token_type' => 'Bearer',
            'guard' => $guardName,
            'abilities' => $abilities,
        ];
    }
}
