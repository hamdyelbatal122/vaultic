<?php

namespace Hamzi\Vaultic\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        return response()->json($this->service->buildRegistrationOptions($user));
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

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $result = $this->service->registerPasskey($user, $request->all());

        return response()->json($result['body'], $result['status']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticationOptions(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(
            $this->service->buildAuthenticationOptions((string) $validated['identifier'])
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $result = $this->service->authenticate((string) $validated['identifier'], $request->all());

        if (isset($result['session']) && is_array($result['session'])) {
            foreach ($result['session'] as $key => $value) {
                $request->session()->put($key, $value);
            }
        }

        return response()->json($result['body'], $result['status']);
    }
}
