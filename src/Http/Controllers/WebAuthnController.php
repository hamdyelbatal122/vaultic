<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Hamzi\Vaultic\Contracts\WebAuthnVerifier;
use Hamzi\Vaultic\Events\AuthenticationFailed;
use Hamzi\Vaultic\Events\PasskeyAuthenticated;
use Hamzi\Vaultic\Events\PasskeyRegistered;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Services\ChallengeStore;
use Throwable;

final class WebAuthnController extends Controller
{
    public function registrationOptions(Request $request, ChallengeStore $store): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $challenge = $store->issue('register', (string) $user->getAuthIdentifier());

        return response()->json([
            'challenge' => $challenge,
            'rp' => [
                'name' => config('vaultic.rp.name'),
                'id' => config('vaultic.rp.id'),
            ],
            'user' => [
                'id' => (string) $user->getAuthIdentifier(),
                'name' => $user->{config('vaultic.user_identifier_column', 'email')} ?? (string) $user->getAuthIdentifier(),
                'displayName' => $user->name ?? $user->{config('vaultic.user_identifier_column', 'email')} ?? 'User',
            ],
            'timeout' => (int) config('vaultic.challenge_timeout_ms', 60_000),
            'attestation' => 'none',
            'authenticatorSelection' => array_filter([
                'userVerification' => config('vaultic.user_verification', 'preferred'),
                'authenticatorAttachment' => config('vaultic.authenticator_attachment'),
            ], static fn (mixed $value): bool => $value !== null),
            'excludeCredentials' => Passkey::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->get(['credential_id'])
                ->map(static fn (Passkey $passkey): array => [
                    'type' => 'public-key',
                    'id' => $passkey->credential_id,
                ])->all(),
        ]);
    }

    public function register(Request $request, WebAuthnVerifier $verifier, ChallengeStore $store): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:'.(int) config('vaultic.device_name_max_length', 100)],
        ]);

        $user = $request->user();
        abort_unless($user !== null, 401);

        $challenge = $store->pull('register', (string) $user->getAuthIdentifier());
        if ($challenge === null) {
            return response()->json(['message' => 'Registration challenge expired.'], 422);
        }

        try {
            $result = $verifier->verifyRegistration(
                payload: $request->all(),
                challenge: $challenge,
                rpId: (string) config('vaultic.rp.id'),
            );
        } catch (Throwable $exception) {
            Event::dispatch(new AuthenticationFailed(reason: $exception->getMessage()));

            return response()->json(['message' => 'Passkey registration verification failed.'], 422);
        }

        if (Passkey::query()->where('credential_id', $result->credentialId)->exists()) {
            throw ValidationException::withMessages([
                'credential' => ['This passkey is already registered.'],
            ]);
        }

        $passkey = Passkey::query()->create([
            'user_id' => $user->getAuthIdentifier(),
            'name' => (string) ($validated['name'] ?? 'Unnamed device'),
            'credential_id' => $result->credentialId,
            'public_key' => $result->publicKey,
            'sign_count' => $result->signCount,
            'transports' => $result->transports,
            'aaguid' => $result->aaguid,
        ]);

        Event::dispatch(new PasskeyRegistered(user: $user, passkey: $passkey));

        return response()->json([
            'message' => 'Passkey registered successfully.',
            'credential_id' => $passkey->credential_id,
        ], 201);
    }

    public function authenticationOptions(Request $request, ChallengeStore $store): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = (string) $validated['identifier'];
        $user = $this->resolveUserByIdentifier($identifier);

        $challenge = $store->issue('authenticate', $identifier);

        return response()->json([
            'challenge' => $challenge,
            'rpId' => config('vaultic.rp.id'),
            'timeout' => (int) config('vaultic.challenge_timeout_ms', 60_000),
            'userVerification' => config('vaultic.user_verification', 'preferred'),
            'allowCredentials' => $user === null
                ? []
                : Passkey::query()
                    ->where('user_id', $user->getAuthIdentifier())
                    ->get(['credential_id'])
                    ->map(static fn (Passkey $passkey): array => [
                        'type' => 'public-key',
                        'id' => $passkey->credential_id,
                    ])->all(),
        ]);
    }

    public function authenticate(Request $request, WebAuthnVerifier $verifier, ChallengeStore $store): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = (string) $validated['identifier'];
        $challenge = $store->pull('authenticate', $identifier);

        if ($challenge === null) {
            Event::dispatch(new AuthenticationFailed(reason: 'Expired authentication challenge.', userIdentifier: $identifier));

            return $this->fallbackResponse($identifier);
        }

        try {
            $result = $verifier->verifyAssertion(
                payload: $request->all(),
                challenge: $challenge,
                rpId: (string) config('vaultic.rp.id'),
            );
        } catch (Throwable $exception) {
            Event::dispatch(new AuthenticationFailed(
                reason: $exception->getMessage(),
                credentialId: (string) $request->input('id'),
                userIdentifier: $identifier
            ));

            return $this->fallbackResponse($identifier);
        }

        $user = $this->resolveUserByIdentifier($identifier);
        $passkey = Passkey::query()->where('credential_id', $result->credentialId)->first();

        if ($user === null || $passkey === null || (string) $passkey->user_id !== (string) $user->getAuthIdentifier() || $passkey->user === null) {
            Event::dispatch(new AuthenticationFailed(
                reason: 'The credential does not belong to the requested account.',
                credentialId: $result->credentialId,
                userIdentifier: $identifier
            ));

            return $this->fallbackResponse($identifier);
        }

        if ($passkey->sign_count > 0 && $result->signCount < $passkey->sign_count) {
            Event::dispatch(new AuthenticationFailed(
                reason: 'Passkey sign counter regression detected.',
                credentialId: $result->credentialId,
                userIdentifier: $identifier
            ));

            return response()->json([
                'message' => 'Potential cloned authenticator detected.',
            ], 409);
        }

        $passkey->forceFill([
            'sign_count' => max($passkey->sign_count, $result->signCount),
            'last_used_at' => now(),
        ])->save();

        Auth::login($passkey->user);
        $request->session()->put('vaultic.passkeys.authenticated', true);

        Event::dispatch(new PasskeyAuthenticated(user: $passkey->user, passkey: $passkey));

        return response()->json([
            'message' => 'Authenticated with passkey.',
            'redirect_to' => (string) config('vaultic.redirect_after_login', '/dashboard'),
        ]);
    }

    private function resolveUserByIdentifier(string $identifier): ?Model
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = (string) config('vaultic.user_model', config('auth.providers.users.model'));
        $identifierColumn = (string) config('vaultic.user_identifier_column', 'email');

        if (! is_a($modelClass, Model::class, true)) {
            return null;
        }

        /** @var Model|null $user */
        $user = $modelClass::query()->where($identifierColumn, $identifier)->first();

        return $user;
    }

    private function fallbackResponse(string $identifier): JsonResponse
    {
        $fallbackDriver = (string) config('vaultic.fallback.driver', 'password');

        if ($fallbackDriver === 'magic_link') {
            $routeName = (string) config('vaultic.fallback.magic_link_route_name', 'login.magic');

            return response()->json([
                'message' => 'Passkey failed. Continue with magic link authentication.',
                'fallback' => 'magic_link',
                'route_name' => $routeName,
                'magic_link' => Route::has($routeName)
                    ? URL::temporarySignedRoute(
                        name: $routeName,
                        expiration: now()->addMinutes((int) config('vaultic.fallback.magic_link_expire_minutes', 10)),
                        parameters: ['email' => $identifier],
                    )
                    : null,
            ], 401);
        }

        return response()->json([
            'message' => 'Passkey failed. Continue with password login.',
            'fallback' => 'password',
            'route_name' => (string) config('vaultic.fallback.password_login_route', 'login'),
        ], 401);
    }
}
