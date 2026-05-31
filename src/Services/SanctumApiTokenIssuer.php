<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;
use Hamzi\Vaultic\Contracts\ApiTokenIssuer;

/**
 * Sanctum-compatible API token issuer.
 *
 * Issues a personal access token via Laravel Sanctum's createToken() method.
 * The authenticatable model must use the HasApiTokens trait.
 */
class SanctumApiTokenIssuer implements ApiTokenIssuer
{
    /**
     * {@inheritDoc}
     */
    public function issueToken(Authenticatable $authenticatable, string $guardName, array $payload = []): array
    {
        if (! method_exists($authenticatable, 'createToken')) {
            throw new RuntimeException(
                'The authenticatable model must expose createToken() to use '
                . self::class
                . '. Install Laravel Sanctum and add HasApiTokens to the model.',
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
            'token_type'   => 'Bearer',
            'guard'        => $guardName,
            'abilities'    => $abilities,
        ];
    }
}
