<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Hamzi\Vaultic\Contracts\WebAuthnService;
use Hamzi\Vaultic\Http\Requests\AuthenticateRequest;
use Hamzi\Vaultic\Http\Requests\AuthenticationOptionsRequest;
use Hamzi\Vaultic\Http\Requests\RegisterPasskeyRequest;
use Hamzi\Vaultic\Http\Requests\RenamePasskeyRequest;
use Hamzi\Vaultic\Models\Passkey;

/**
 * Handles WebAuthn registration, authentication, and passkey management.
 */
class WebAuthnController extends Controller
{
    public function __construct(
        private readonly WebAuthnService $service,
    ) {
    }

    /**
     * Return WebAuthn registration options for the authenticated user.
     */
    public function registrationOptions(Request $request): JsonResponse
    {
        [$guardName] = $this->resolveChannelContext($request);
        $user = $this->resolveAuthenticatedUser($request, $guardName);

        return response()->json($this->service->buildRegistrationOptions($user, $guardName));
    }

    /**
     * Register a new passkey for the authenticated user.
     */
    public function register(RegisterPasskeyRequest $request): JsonResponse
    {
        [$guardName] = $this->resolveChannelContext($request);
        $user = $this->resolveAuthenticatedUser($request, $guardName);

        $result = $this->service->registerPasskey($user, $request->all(), $guardName);

        return response()->json($result['body'], $result['status']);
    }

    /**
     * Return WebAuthn authentication options.
     */
    public function authenticationOptions(AuthenticationOptionsRequest $request): JsonResponse
    {
        [$guardName] = $this->resolveChannelContext($request);
        $validated = $request->validated();

        return response()->json(
            $this->service->buildAuthenticationOptions(
                isset($validated['identifier']) ? (string) $validated['identifier'] : null,
                isset($validated['guard']) ? (string) $validated['guard'] : $guardName,
            ),
        );
    }

    /**
     * Authenticate using a WebAuthn assertion.
     */
    public function authenticate(AuthenticateRequest $request): JsonResponse
    {
        [$guardName, $stateful] = $this->resolveChannelContext($request);
        $validated = $request->validated();

        $resolvedGuard = isset($validated['guard']) ? (string) $validated['guard'] : $guardName;

        $result = $this->service->authenticate(
            isset($validated['identifier']) ? (string) $validated['identifier'] : null,
            $request->all(),
            $resolvedGuard,
            $stateful,
            (string) $request->ip(),
        );

        if (isset($result['session']) && is_array($result['session'])) {
            foreach ($result['session'] as $key => $value) {
                $request->session()->put($key, $value);
            }
        }

        return response()->json($result['body'], $result['status']);
    }

    /**
     * Delete an owned passkey.
     */
    public function destroy(Request $request, Passkey $passkey): JsonResponse|RedirectResponse
    {
        [$guardName] = $this->resolveChannelContext($request);
        $user = $this->resolveAuthenticatedUser($request, $guardName);

        if (! $this->service->deletePasskey($user, $passkey)) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'       => 'Passkey deleted successfully.',
                'credential_id' => $passkey->credential_id,
            ]);
        }

        return back()->with('vaultic.status', 'Passkey deleted successfully.');
    }

    /**
     * Rename an owned passkey.
     */
    public function rename(RenamePasskeyRequest $request, Passkey $passkey): JsonResponse|RedirectResponse
    {
        [$guardName] = $this->resolveChannelContext($request);
        $user = $this->resolveAuthenticatedUser($request, $guardName);

        if (! $this->service->renamePasskey($user, $passkey, $request->validated('name'))) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Passkey renamed successfully.',
                'name'    => $passkey->fresh()?->name,
            ]);
        }

        return back()->with('vaultic.status', 'Passkey renamed successfully.');
    }

    // -------------------------------------------------------------------------
    //  Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the guard name and stateful flag from the route channel context.
     *
     * @return array{0: string, 1: bool}
     */
    private function resolveChannelContext(Request $request): array
    {
        $routeName = $request->route() ? (string) $request->route()->getName() : '';
        $channel = str_starts_with($routeName, 'vaultic.api.') ? 'api' : 'web';
        $channelConfig = (array) config('vaultic.routes.' . $channel, []);

        return [
            (string) ($channelConfig['guard'] ?? config('vaultic.auth.default_guard', 'web')),
            (bool) ($channelConfig['stateful'] ?? true),
        ];
    }

    /**
     * Resolve and return the authenticated user, or abort 401.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    private function resolveAuthenticatedUser(Request $request, string $guardName)
    {
        $user = $request->user($guardName) ?: Auth::guard($guardName)->user();

        if ($user === null) {
            abort(401);
        }

        return $user;
    }
}
