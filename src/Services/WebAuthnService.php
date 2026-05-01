<?php

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
use Throwable;

class WebAuthnService implements WebAuthnServiceContract
{
    /** @var WebAuthnVerifier */
    private $verifier;

    /** @var ChallengeStore */
    private $challengeStore;

    /** @var PasskeyRepository */
    private $passkeyRepository;

    /** @var ApiTokenIssuer */
    private $apiTokenIssuer;

    /**
     * @param WebAuthnVerifier $verifier
     * @param ChallengeStore $challengeStore
     * @param PasskeyRepository $passkeyRepository
     * @param ApiTokenIssuer $apiTokenIssuer
     */
    public function __construct(
        WebAuthnVerifier $verifier,
        ChallengeStore $challengeStore,
        PasskeyRepository $passkeyRepository,
        ApiTokenIssuer $apiTokenIssuer
    )
    {
        $this->verifier = $verifier;
        $this->challengeStore = $challengeStore;
        $this->passkeyRepository = $passkeyRepository;
        $this->apiTokenIssuer = $apiTokenIssuer;
    }

    /**
     * @param mixed $user
     * @return array<string, mixed>
     */
    public function buildRegistrationOptions($user, $guardName = null)
    {
        $userId = (string) $user->getAuthIdentifier();
        $guardConfig = $this->getGuardConfig($guardName);
        $identifierColumn = (string) $guardConfig['identifier_column'];

        $challenge = $this->challengeStore->issue('register', $userId);

        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => config('vaultic.rp.name'),
                'id' => config('vaultic.rp.id'),
            ],
            'user' => [
                'id' => $userId,
                'name' => $user->{$identifierColumn} ?: $userId,
                'displayName' => isset($user->name) ? $user->name : ($user->{$identifierColumn} ?: 'User'),
            ],
            'timeout' => (int) config('vaultic.challenge_timeout_ms', 60000),
            'attestation' => 'none',
            'authenticatorSelection' => array_filter([
                'userVerification' => config('vaultic.user_verification', 'preferred'),
                'authenticatorAttachment' => config('vaultic.authenticator_attachment'),
            ], function ($value) {
                return $value !== null;
            }),
            'excludeCredentials' => $this->passkeyRepository->listCredentialDescriptorsForAuthenticatable($user),
            'vaultic' => [
                'guard' => (string) $guardConfig['guard'],
                'stateful' => (bool) $guardConfig['stateful'],
            ],
        ];
    }

    /**
     * @param mixed $user
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function registerPasskey($user, array $payload, $guardName = null)
    {
        $userId = (string) $user->getAuthIdentifier();
        $challenge = $this->challengeStore->pull('register', $userId);

        if ($challenge === null) {
            return [
                'status' => 422,
                'body' => ['message' => 'Registration challenge expired.'],
            ];
        }

        try {
            $result = $this->verifier->verifyRegistration(
                $payload,
                $challenge,
                (string) config('vaultic.rp.id')
            );
        } catch (Throwable $exception) {
            Event::dispatch(new AuthenticationFailed($exception->getMessage()));

            return [
                'status' => 422,
                'body' => ['message' => 'Passkey registration verification failed.'],
            ];
        }

        if ($this->passkeyRepository->credentialExists($result->getCredentialId())) {
            throw ValidationException::withMessages([
                'credential' => ['This passkey is already registered.'],
            ]);
        }

        $passkey = $this->passkeyRepository->createForAuthenticatable($user, [
            'name' => isset($payload['name']) && $payload['name'] !== ''
                ? (string) $payload['name']
                : 'Unnamed device',
            'credential_id' => $result->getCredentialId(),
            'public_key' => $result->getPublicKey(),
            'sign_count' => $result->getSignCount(),
            'transports' => $result->getTransports(),
            'aaguid' => $result->getAaguid(),
        ]);

        Event::dispatch(new PasskeyRegistered($user, $passkey));

        return [
            'status' => 201,
            'body' => [
                'message' => 'Passkey registered successfully.',
                'credential_id' => $passkey->credential_id,
                'guard' => (string) $this->getGuardConfig($guardName)['guard'],
            ],
        ];
    }

    /**
     * @param string $identifier
     * @return array<string, mixed>
     */
    public function buildAuthenticationOptions($identifier, $guardName = null)
    {
        $guardConfig = $this->getGuardConfig($guardName);
        $user = $this->resolveUserByIdentifier($identifier, $guardConfig);
        $challenge = $this->challengeStore->issue('authenticate', $identifier);

        return [
            'challenge' => $challenge,
            'rpId' => config('vaultic.rp.id'),
            'timeout' => (int) config('vaultic.challenge_timeout_ms', 60000),
            'userVerification' => config('vaultic.user_verification', 'preferred'),
            'allowCredentials' => $user === null
                ? []
                : $this->passkeyRepository->listCredentialDescriptorsForAuthenticatable($user),
            'vaultic' => [
                'guard' => (string) $guardConfig['guard'],
                'stateful' => (bool) $guardConfig['stateful'],
            ],
        ];
    }

    /**
     * @param string $identifier
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function authenticate($identifier, array $payload, $guardName = null, $stateful = null)
    {
        $guardConfig = $this->getGuardConfig($guardName);
        $isStateful = $stateful === null ? (bool) $guardConfig['stateful'] : (bool) $stateful;
        $challenge = $this->challengeStore->pull('authenticate', $identifier);

        if ($challenge === null) {
            Event::dispatch(new AuthenticationFailed('Expired authentication challenge.', null, $identifier));

            return $this->fallbackResponse($identifier, $guardConfig, $isStateful);
        }

        try {
            $result = $this->verifier->verifyAssertion(
                $payload,
                $challenge,
                (string) config('vaultic.rp.id')
            );
        } catch (Throwable $exception) {
            Event::dispatch(new AuthenticationFailed(
                $exception->getMessage(),
                isset($payload['id']) ? (string) $payload['id'] : null,
                $identifier
            ));

            return $this->fallbackResponse($identifier, $guardConfig, $isStateful);
        }

        $user = $this->resolveUserByIdentifier($identifier, $guardConfig);
        $passkey = $this->passkeyRepository->findByCredentialId($result->getCredentialId());
        $authenticatable = $passkey ? $passkey->authenticatable : null;

        if (
            $user === null
            || $passkey === null
            || $authenticatable === null
            || get_class($authenticatable) !== get_class($user)
            || (string) $authenticatable->getAuthIdentifier() !== (string) $user->getAuthIdentifier()
        ) {
            Event::dispatch(new AuthenticationFailed(
                'The credential does not belong to the requested account.',
                $result->getCredentialId(),
                $identifier
            ));

            return $this->fallbackResponse($identifier, $guardConfig, $isStateful);
        }

        if ((int) $passkey->sign_count > 0 && (int) $result->getSignCount() < (int) $passkey->sign_count) {
            Event::dispatch(new AuthenticationFailed(
                'Passkey sign counter regression detected.',
                $result->getCredentialId(),
                $identifier
            ));

            return [
                'status' => 409,
                'body' => ['message' => 'Potential cloned authenticator detected.'],
            ];
        }

        $this->passkeyRepository->markAsUsed($passkey, $result->getSignCount());

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
                'message' => 'Authenticated with passkey.',
                'redirect_to' => (string) config('vaultic.redirect_after_login', '/dashboard'),
                'guard' => (string) $guardConfig['guard'],
                'stateful' => $isStateful,
                'user' => [
                    'id' => (string) $authenticatable->getAuthIdentifier(),
                    'type' => get_class($authenticatable),
                ],
                'tokens' => $tokenPayload,
            ],
            'session' => $sessionPayload,
            'user' => $authenticatable,
            'passkey' => $passkey,
        ];
    }

    /**
     * @param string $identifier
     * @param array<string, mixed> $guardConfig
     * @return Model|null
     */
    private function resolveUserByIdentifier($identifier, array $guardConfig)
    {
        $modelClass = (string) $guardConfig['provider_model'];
        $identifierColumn = (string) $guardConfig['identifier_column'];

        if (!is_a($modelClass, Model::class, true)) {
            return null;
        }

        return $modelClass::query()->where($identifierColumn, $identifier)->first();
    }

    /**
     * @param string $identifier
     * @param array<string, mixed> $guardConfig
     * @param bool $stateful
     * @return array<string, mixed>
     */
    private function fallbackResponse($identifier, array $guardConfig, $stateful)
    {
        $fallbackDriver = (string) config('vaultic.fallback.driver', 'password');

        if ($fallbackDriver === 'magic_link') {
            $routeName = (string) config('vaultic.fallback.magic_link_route_name', 'login.magic');
            $magicLink = null;
            $urlGenerator = app('url');

            if (Route::has($routeName) && method_exists($urlGenerator, 'temporarySignedRoute')) {
                $magicLink = URL::temporarySignedRoute(
                    $routeName,
                    now()->addMinutes((int) config('vaultic.fallback.magic_link_expire_minutes', 10)),
                    ['email' => $identifier]
                );
            }

            return [
                'status' => 401,
                'body' => [
                    'message' => 'Passkey failed. Continue with magic link authentication.',
                    'fallback' => 'magic_link',
                    'guard' => (string) $guardConfig['guard'],
                    'stateful' => (bool) $stateful,
                    'route_name' => $routeName,
                    'magic_link' => $magicLink,
                ],
            ];
        }

        return [
            'status' => 401,
            'body' => [
                'message' => 'Passkey failed. Continue with password login.',
                'fallback' => 'password',
                'guard' => (string) $guardConfig['guard'],
                'stateful' => (bool) $stateful,
                'route_name' => (string) config('vaultic.fallback.password_login_route', 'login'),
            ],
        ];
    }

    /**
     * @param string|null $guardName
     * @return array<string, mixed>
     */
    private function getGuardConfig($guardName = null)
    {
        $resolvedGuardName = $guardName ?: (string) config('vaultic.auth.default_guard', 'web');
        $guardConfig = (array) config('vaultic.auth.guards.'.$resolvedGuardName, []);

        return array_merge([
            'guard' => $resolvedGuardName,
            'provider_model' => (string) config('vaultic.user_model', \App\Models\User::class),
            'identifier_column' => (string) config('vaultic.user_identifier_column', 'email'),
            'stateful' => true,
            'remember' => false,
            'token_issuer' => null,
        ], $guardConfig);
    }

    /**
     * @param Authenticatable $authenticatable
     * @param array<string, mixed> $guardConfig
     * @return void
     */
    private function loginAuthenticatable(Authenticatable $authenticatable, array $guardConfig)
    {
        $guard = Auth::guard((string) $guardConfig['guard']);

        if (method_exists($guard, 'login')) {
            $guard->login($authenticatable, (bool) ($guardConfig['remember'] ?? false));
        }
    }

    /**
     * @param Authenticatable $authenticatable
     * @param array<string, mixed> $guardConfig
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function issueApiToken(Authenticatable $authenticatable, array $guardConfig, array $payload)
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
}
