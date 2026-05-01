<?php

namespace Hamzi\Vaultic\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Contracts\WebAuthnService;

class WebAuthnController extends Controller
{
    /** @var WebAuthnService */
    private $service;

    /**
     * @param WebAuthnService $service
     */
    public function __construct(WebAuthnService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function registrationOptions(Request $request)
    {
        list($guardName) = $this->resolveChannelContext($request);
        $user = $request->user($guardName) ?: Auth::guard($guardName)->user();

        if ($user === null) {
            abort(401);
        }

        return response()->json($this->service->buildRegistrationOptions($user, $guardName));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:' . (int) config('vaultic.device_name_max_length', 100)],
        ]);

        list($guardName) = $this->resolveChannelContext($request);
        $user = $request->user($guardName) ?: Auth::guard($guardName)->user();

        if ($user === null) {
            abort(401);
        }

        $result = $this->service->registerPasskey($user, $request->all(), $guardName);

        return response()->json($result['body'], $result['status']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticationOptions(Request $request)
    {
        list($guardName) = $this->resolveChannelContext($request);
        $validated = $request->validate([
            'identifier' => ['nullable', 'string', 'max:255'],
            'guard' => ['nullable', 'string', 'max:50'],
        ]);

        return response()->json(
            $this->service->buildAuthenticationOptions(
                isset($validated['identifier']) ? (string) $validated['identifier'] : null,
                isset($validated['guard']) ? (string) $validated['guard'] : $guardName
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request)
    {
        list($guardName, $stateful) = $this->resolveChannelContext($request);
        $validated = $request->validate([
            'identifier' => ['nullable', 'string', 'max:255'],
            'guard' => ['nullable', 'string', 'max:50'],
        ]);

        $resolvedGuard = isset($validated['guard']) ? (string) $validated['guard'] : $guardName;
        $result = $this->service->authenticate(
            isset($validated['identifier']) ? (string) $validated['identifier'] : null,
            $request->all(),
            $resolvedGuard,
            $stateful
        );

        if (isset($result['session']) && is_array($result['session'])) {
            foreach ($result['session'] as $key => $value) {
                $request->session()->put($key, $value);
            }
        }

        return response()->json($result['body'], $result['status']);
    }

    /**
     * @param Request $request
     * @param Passkey $passkey
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, Passkey $passkey)
    {
        list($guardName) = $this->resolveChannelContext($request);
        $user = $request->user($guardName) ?: Auth::guard($guardName)->user();

        if ($user === null) {
            abort(401);
        }

        if (! $this->service->deletePasskey($user, $passkey)) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Passkey deleted successfully.',
                'credential_id' => $passkey->credential_id,
            ]);
        }

        return back()->with('vaultic.status', 'Passkey deleted successfully.');
    }

    /**
     * @param Request $request
     * @return array{0:string,1:bool}
     */
    private function resolveChannelContext(Request $request): array
    {
        $routeName = $request->route() ? (string) $request->route()->getName() : '';
        $channel = str_starts_with($routeName, 'vaultic.api.') ? 'api' : 'web';
        $channelConfig = (array) config('vaultic.routes.'.$channel, []);

        return [
            (string) ($channelConfig['guard'] ?? config('vaultic.auth.default_guard', 'web')),
            (bool) ($channelConfig['stateful'] ?? true),
        ];
    }
}
