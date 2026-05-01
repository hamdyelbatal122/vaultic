<?php

namespace Hamzi\Vaultic\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
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

    /**
     * @param WebAuthnVerifier $verifier
     * @param ChallengeStore $challengeStore
     * @param PasskeyRepository $passkeyRepository
     */
    public function __construct(WebAuthnVerifier $verifier, ChallengeStore $challengeStore, PasskeyRepository $passkeyRepository)
    {
        $this->verifier = $verifier;
        $this->challengeStore = $challengeStore;
        $this->passkeyRepository = $passkeyRepository;
    }

    /**
     * @param mixed $user
     * @return array<string, mixed>
     */
    public function buildRegistrationOptions($user)
    {
        $userId = (string) $user->getAuthIdentifier();
        $identifierColumn = (string) config('vaultic.user_identifier_column', 'email');

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
            'excludeCredentials' => $this->passkeyRepository->listCredentialDescriptorsForUser($user->getAuthIdentifier()),
        ];
    }

    /**
     * @param mixed $user
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function registerPasskey($user, array $payload)
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

        $passkey = $this->passkeyRepository->createForUser($user->getAuthIdentifier(), [
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
            ],
        ];
    }

    /**
     * @param string $identifier
     * @return array<string, mixed>
     */
    public function buildAuthenticationOptions($identifier)
    {
        $user = $this->resolveUserByIdentifier($identifier);
        $challenge = $this->challengeStore->issue('authenticate', $identifier);

        return [
            'challenge' => $challenge,
            'rpId' => config('vaultic.rp.id'),
            'timeout' => (int) config('vaultic.challenge_timeout_ms', 60000),
            'userVerification' => config('vaultic.user_verification', 'preferred'),
            'allowCredentials' => $user === null
                ? []
                : $this->passkeyRepository->listCredentialDescriptorsForUser($user->getAuthIdentifier()),
        ];
    }

    /**
     * @param string $identifier
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function authenticate($identifier, array $payload)
    {
        $challenge = $this->challengeStore->pull('authenticate', $identifier);

        if ($challenge === null) {
            Event::dispatch(new AuthenticationFailed('Expired authentication challenge.', null, $identifier));

            return $this->fallbackResponse($identifier);
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

            return $this->fallbackResponse($identifier);
        }

        $user = $this->resolveUserByIdentifier($identifier);
        $passkey = $this->passkeyRepository->findByCredentialId($result->getCredentialId());

        if ($user === null || $passkey === null || (string) $passkey->user_id !== (string) $user->getAuthIdentifier() || $passkey->user === null) {
            Event::dispatch(new AuthenticationFailed(
                'The credential does not belong to the requested account.',
                $result->getCredentialId(),
                $identifier
            ));

            return $this->fallbackResponse($identifier);
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

        Auth::login($passkey->user);

        return [
            'status' => 200,
            'body' => [
                'message' => 'Authenticated with passkey.',
                'redirect_to' => (string) config('vaultic.redirect_after_login', '/dashboard'),
            ],
            'session' => ['vaultic.passkeys.authenticated' => true],
            'user' => $passkey->user,
            'passkey' => $passkey,
        ];
    }

    /**
     * @param string $identifier
     * @return Model|null
     */
    private function resolveUserByIdentifier($identifier)
    {
        $modelClass = (string) config('vaultic.user_model', config('auth.providers.users.model'));
        $identifierColumn = (string) config('vaultic.user_identifier_column', 'email');

        if (!is_a($modelClass, Model::class, true)) {
            return null;
        }

        return $modelClass::query()->where($identifierColumn, $identifier)->first();
    }

    /**
     * @param string $identifier
     * @return array<string, mixed>
     */
    private function fallbackResponse($identifier)
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
                'route_name' => (string) config('vaultic.fallback.password_login_route', 'login'),
            ],
        ];
    }
}
