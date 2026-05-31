<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Hamzi\Vaultic\Contracts\ApiTokenIssuer;
use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Hamzi\Vaultic\Contracts\WebAuthnService as WebAuthnServiceContract;
use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Events\AuthenticationFailed;
use Hamzi\Vaultic\Events\PasskeyAuthenticated;
use Hamzi\Vaultic\Events\PasskeyRegistered;
use Hamzi\Vaultic\Events\PasskeyRenamed;
use Hamzi\Vaultic\Models\Passkey;
use Throwable;

/**
 * Orchestrates WebAuthn registration, authentication, and passkey management.
 *
 * This service acts as the single entry point for all WebAuthn business logic,
 * delegating verification to the bound WebAuthnVerifier and persistence to the
 * PasskeyRepository.
 */
class WebAuthnService implements WebAuthnServiceContract
{
    public function __construct(
        private readonly WebAuthnVerifier $verifier,
        private readonly ChallengeStore $challengeStore,
        private readonly PasskeyRepository $passkeyRepository,
        private readonly ApiTokenIssuer $apiTokenIssuer,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function buildRegistrationOptions(Authenticatable $user, ?string $guardName = null): array
    {
        $userId = (string) $user->getAuthIdentifier();
        $guardConfig = $this->getGuardConfig($guardName);
        $identifierColumn = (string) $guardConfig['identifier_column'];

        $challenge = $this->challengeStore->issue('register', $userId);

        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => config('vaultic.rp.name'),
                'id'   => config('vaultic.rp.id'),
            ],
            'user' => [
                'id'          => $userId,
                'name'        => $user->{$identifierColumn} ?: $userId,
                'displayName' => isset($user->name) ? $user->name : ($user->{$identifierColumn} ?: 'User'),
            ],
            'timeout'     => (int) config('vaultic.challenge_timeout_ms', 60000),
            'attestation' => 'none',
            'authenticatorSelection' => array_filter([
                'userVerification'        => config('vaultic.user_verification', 'preferred'),
                'residentKey'             => config('vaultic.resident_key', 'required'),
                'authenticatorAttachment' => config('vaultic.authenticator_attachment'),
            ], fn ($value) => $value !== null),
            'hints' => array_values(array_filter(
                (array) config('vaultic.authenticator_hints', ['client-device', 'hybrid'])
            )),
            'excludeCredentials' => $this->passkeyRepository->listCredentialDescriptorsForAuthenticatable($user),
            'vaultic' => [
                'guard'    => (string) $guardConfig['guard'],
                'stateful' => (bool) $guardConfig['stateful'],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function registerPasskey(Authenticatable $user, array $payload, ?string $guardName = null): array
    {
        $userId = (string) $user->getAuthIdentifier();
        $challenge = $this->challengeStore->pull('register', $userId);

        if ($challenge === null) {
            return [
                'status' => 422,
                'body'   => ['message' => 'Registration challenge expired.'],
            ];
        }

        try {
            $result = $this->verifier->verifyRegistration(
                $payload,
                $challenge,
                (string) config('vaultic.rp.id'),
            );
        } catch (Throwable $exception) {
            Event::dispatch(new AuthenticationFailed($exception->getMessage()));

            return [
                'status' => 422,
                'body'   => ['message' => 'Passkey registration verification failed.'],
            ];
        }

        if ($this->passkeyRepository->credentialExists($result->getCredentialId())) {
            throw ValidationException::withMessages([
                'credential' => ['This passkey is already registered.'],
            ]);
        }

        $passkey = $this->passkeyRepository->createForAuthenticatable($user, [
            'name'          => isset($payload['name']) && $payload['name'] !== ''
                ? (string) $payload['name']
                : 'Unnamed device',
            'credential_id' => $result->getCredentialId(),
            'public_key'    => $result->getPublicKey(),
            'sign_count'    => $result->getSignCount(),
            'transports'    => $result->getTransports(),
            'aaguid'        => $result->getAaguid(),
        ]);

        Event::dispatch(new PasskeyRegistered($user, $passkey));

        return [
            'status' => 201,
            'body' => [
                'message'       => 'Passkey registered successfully.',
                'credential_id' => $passkey->credential_id,
                'guard'         => (string) $this->getGuardConfig($guardName)['guard'],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function buildAuthenticationOptions(?string $identifier, ?string $guardName = null): array
    {
        $guardConfig = $this->getGuardConfig($guardName);
        $normalizedIdentifier = is_string($identifier) ? trim($identifier) : '';

        $user = $normalizedIdentifier !== ''
            ? $this->resolveUserByIdentifier($normalizedIdentifier, $guardConfig)
            : null;

        $challengeKey = $normalizedIdentifier !== ''
            ? $normalizedIdentifier
            : 'discoverable:' . (string) $guardConfig['guard'] . ':' . bin2hex(random_bytes(16));

        $challenge = $this->challengeStore->issue('authenticate', $challengeKey);

        return [
            'challenge'        => $challenge,
            'rpId'             => config('vaultic.rp.id'),
            'timeout'          => (int) config('vaultic.challenge_timeout_ms', 60000),
            'userVerification' => config('vaultic.user_verification', 'preferred'),
            'hints' => array_values(array_filter(
                (array) config('vaultic.authenticator_hints', ['client-device', 'hybrid'])
            )),
            'allowCredentials' => $user === null
                ? []
                : $this->passkeyRepository->listCredentialDescriptorsForAuthenticatable($user),
            'vaultic' => [
                'guard'         => (string) $guardConfig['guard'],
                'stateful'      => (bool) $guardConfig['stateful'],
                'challenge_key' => $challengeKey,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(
        ?string $identifier,
        array $payload,
        ?string $guardName = null,
        ?bool $stateful = null,
        ?string $clientIp = null,
    ): array {
        $guardConfig = $this->getGuardConfig($guardName);
        $isStateful = $stateful === null ? (bool) $guardConfig['stateful'] : $stateful;
        $normalizedIdentifier = is_string($identifier) ? trim($identifier) : '';

        $challengeKey = isset($payload['challenge_key']) && is_string($payload['challenge_key'])
            ? trim((string) $payload['challenge_key'])
            : $normalizedIdentifier;

        $challenge = $challengeKey !== '' ? $this->challengeStore->pull('authenticate', $challengeKey) : null;

        if ($challenge === null) {
            Event::dispatch(new AuthenticationFailed('Expired authentication challenge.', null, $normalizedIdentifier));

            return $this->fallbackResponse($normalizedIdentifier, $guardConfig, $isStateful);
        }

        $credentialId = $this->extractCredentialId($payload);
        $passkey = null;

        if ($credentialId !== null) {
            $passkey = $this->passkeyRepository->findByCredentialId($credentialId);

            if ($passkey !== null) {
                $payload['credentialPublicKey'] = (string) $passkey->public_key;
                $payload['signCount'] = (int) $passkey->sign_count;
            }
        }

        try {
            $result = $this->verifier->verifyAssertion(
                $payload,
                $challenge,
                (string) config('vaultic.rp.id'),
            );
        } catch (Throwable $exception) {
            Event::dispatch(new AuthenticationFailed(
                $exception->getMessage(),
                isset($payload['id']) ? (string) $payload['id'] : null,
                $normalizedIdentifier,
            ));

            return $this->fallbackResponse($normalizedIdentifier, $guardConfig, $isStateful);
        }

        $user = $normalizedIdentifier !== ''
            ? $this->resolveUserByIdentifier($normalizedIdentifier, $guardConfig)
            : null;

        $passkey = $passkey ?: $this->passkeyRepository->findByCredentialId($result->getCredentialId());
        $authenticatable = $passkey?->authenticatable;

        if (! $this->isValidAuthenticatable($authenticatable, $user, $normalizedIdentifier, $guardConfig)) {
            Event::dispatch(new AuthenticationFailed(
                'The credential does not belong to the requested account.',
                $result->getCredentialId(),
                $normalizedIdentifier,
            ));

            return $this->fallbackResponse($normalizedIdentifier, $guardConfig, $isStateful);
        }

        if ($this->isSignCounterRegression($passkey, $result->getSignCount())) {
            Event::dispatch(new AuthenticationFailed(
                'Passkey sign counter regression detected.',
                $result->getCredentialId(),
                $normalizedIdentifier,
            ));

            return [
                'status' => 409,
                'body'   => ['message' => 'Potential cloned authenticator detected.'],
            ];
        }

        $storeLastUsedIp = (bool) config('vaultic.security.store_last_used_ip', true);
        $this->passkeyRepository->markAsUsed(
            $passkey,
            $result->getSignCount(),
            $storeLastUsedIp ? $clientIp : null,
        );

        $sessionPayload = [];
        $tokenPayload = [];

        if ($isStateful) {
            $this->loginAuthenticatable($authenticatable, $guardConfig);
            $sessionPayload = [
                (string) config('vaultic.auth.session_key', 'vaultic.passkeys.authenticated') => true,
            ];
        } else {
            $tokenPayload = $this->issueApiToken($authenticatable, $guardConfig, $payload);
        }

        Event::dispatch(new PasskeyAuthenticated($authenticatable, $passkey));

        return [
            'status' => 200,
            'body' => [
                'message'     => 'Authenticated with passkey.',
                'redirect_to' => (string) config('vaultic.redirect_after_login', '/dashboard'),
                'guard'       => (string) $guardConfig['guard'],
                'stateful'    => $isStateful,
                'user' => [
                    'id'   => (string) $authenticatable->getAuthIdentifier(),
                    'type' => get_class($authenticatable),
                ],
                'tokens' => $tokenPayload,
            ],
            'session' => $sessionPayload,
            'user'    => $authenticatable,
            'passkey' => $passkey,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function deletePasskey(Authenticatable $user, Passkey $passkey): bool
    {
        return $this->passkeyRepository->deleteForAuthenticatable($user, $passkey);
    }

    /**
     * {@inheritDoc}
     */
    public function renamePasskey(Authenticatable $user, Passkey $passkey, string $name): bool
    {
        $oldName = $passkey->name;

        $renamed = $this->passkeyRepository->renameForAuthenticatable($user, $passkey, $name);

        if ($renamed) {
            Event::dispatch(new PasskeyRenamed($user, $passkey, $oldName, $name));
        }

        return $renamed;
    }

    // -------------------------------------------------------------------------
    //  Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a user model by identifier within the guard's configured provider.
     *
     * @param  string                $identifier
     * @param  array<string, mixed>  $guardConfig
     * @return Model|null
     */
    private function resolveUserByIdentifier(string $identifier, array $guardConfig): ?Model
    {
        $modelClass = (string) $guardConfig['provider_model'];
        $identifierColumn = (string) $guardConfig['identifier_column'];

        if (! is_a($modelClass, Model::class, true)) {
            return null;
        }

        return $modelClass::query()->where($identifierColumn, $identifier)->first();
    }

    /**
     * Build a fallback authentication response.
     *
     * @param  string                $identifier
     * @param  array<string, mixed>  $guardConfig
     * @param  bool                  $stateful
     * @return array<string, mixed>
     */
    private function fallbackResponse(string $identifier, array $guardConfig, bool $stateful): array
    {
        $fallbackDriver = (string) config('vaultic.fallback.driver', 'password');

        if ($fallbackDriver === 'magic_link') {
            return $this->buildMagicLinkFallback($identifier, $guardConfig, $stateful);
        }

        return [
            'status' => 401,
            'body' => [
                'message'    => 'Passkey failed. Continue with password login.',
                'fallback'   => 'password',
                'guard'      => (string) $guardConfig['guard'],
                'stateful'   => $stateful,
                'route_name' => (string) config('vaultic.fallback.password_login_route', 'login'),
            ],
        ];
    }

    /**
     * Build a magic-link fallback response.
     *
     * @param  string                $identifier
     * @param  array<string, mixed>  $guardConfig
     * @param  bool                  $stateful
     * @return array<string, mixed>
     */
    private function buildMagicLinkFallback(string $identifier, array $guardConfig, bool $stateful): array
    {
        $routeName = (string) config('vaultic.fallback.magic_link_route_name', 'login.magic');
        $magicLink = null;
        $urlGenerator = app('url');

        if (Route::has($routeName) && method_exists($urlGenerator, 'temporarySignedRoute')) {
            $magicLink = URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes((int) config('vaultic.fallback.magic_link_expire_minutes', 10)),
                ['email' => $identifier],
            );
        }

        return [
            'status' => 401,
            'body' => [
                'message'    => 'Passkey failed. Continue with magic link authentication.',
                'fallback'   => 'magic_link',
                'guard'      => (string) $guardConfig['guard'],
                'stateful'   => $stateful,
                'route_name' => $routeName,
                'magic_link' => $magicLink,
            ],
        ];
    }

    /**
     * Resolve the guard configuration, merging defaults.
     *
     * @param  string|null  $guardName
     * @return array<string, mixed>
     */
    private function getGuardConfig(?string $guardName = null): array
    {
        $resolvedGuardName = $guardName ?: (string) config('vaultic.auth.default_guard', 'web');
        $guardConfig = (array) config('vaultic.auth.guards.' . $resolvedGuardName, []);

        return array_merge([
            'guard'             => $resolvedGuardName,
            'provider_model'    => (string) config('vaultic.user_model', \App\Models\User::class),
            'identifier_column' => (string) config('vaultic.user_identifier_column', 'email'),
            'stateful'          => true,
            'remember'          => false,
            'token_issuer'      => null,
        ], $guardConfig);
    }

    /**
     * Log the authenticatable into the session-based guard.
     *
     * @param  Authenticatable       $authenticatable
     * @param  array<string, mixed>  $guardConfig
     */
    private function loginAuthenticatable(Authenticatable $authenticatable, array $guardConfig): void
    {
        $guard = Auth::guard((string) $guardConfig['guard']);

        if (method_exists($guard, 'login')) {
            $guard->login($authenticatable, (bool) ($guardConfig['remember'] ?? false));
        }
    }

    /**
     * Issue an API token via the configured or default token issuer.
     *
     * @param  Authenticatable       $authenticatable
     * @param  array<string, mixed>  $guardConfig
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function issueApiToken(Authenticatable $authenticatable, array $guardConfig, array $payload): array
    {
        $issuerClass = isset($guardConfig['token_issuer']) && is_string($guardConfig['token_issuer'])
            ? $guardConfig['token_issuer']
            : null;

        if ($issuerClass !== null && class_exists($issuerClass)) {
            $issuer = app($issuerClass);

            if ($issuer instanceof ApiTokenIssuer) {
                return $issuer->issueToken($authenticatable, (string) $guardConfig['guard'], $payload);
            }
        }

        return $this->apiTokenIssuer->issueToken($authenticatable, (string) $guardConfig['guard'], $payload);
    }

    /**
     * Extract the credential ID from the assertion payload.
     *
     * @param  array<string, mixed>  $payload
     * @return string|null
     */
    private function extractCredentialId(array $payload): ?string
    {
        if (isset($payload['rawId']) && is_string($payload['rawId'])) {
            $id = trim($payload['rawId']);
            return $id !== '' ? $id : null;
        }

        if (isset($payload['id']) && is_string($payload['id'])) {
            $id = trim($payload['id']);
            return $id !== '' ? $id : null;
        }

        return null;
    }

    /**
     * Validate that the resolved authenticatable matches the expected guard and user.
     *
     * @param  mixed                 $authenticatable
     * @param  Model|null            $user
     * @param  string                $normalizedIdentifier
     * @param  array<string, mixed>  $guardConfig
     * @return bool
     */
    private function isValidAuthenticatable(
        mixed $authenticatable,
        ?Model $user,
        string $normalizedIdentifier,
        array $guardConfig,
    ): bool {
        if ($authenticatable === null) {
            return false;
        }

        if (! is_a($authenticatable, (string) $guardConfig['provider_model'])) {
            return false;
        }

        if ($normalizedIdentifier !== '' && $user !== null) {
            $sameClass = get_class($authenticatable) === get_class($user);
            $sameId = (string) $authenticatable->getAuthIdentifier() === (string) $user->getAuthIdentifier();

            if (! $sameClass || ! $sameId) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether the sign counter has regressed (possible cloned authenticator).
     *
     * @param  Passkey|null  $passkey
     * @param  int           $newSignCount
     * @return bool
     */
    private function isSignCounterRegression(?Passkey $passkey, int $newSignCount): bool
    {
        if ($passkey === null) {
            return false;
        }

        return (int) $passkey->sign_count > 0 && $newSignCount < (int) $passkey->sign_count;
    }
}
