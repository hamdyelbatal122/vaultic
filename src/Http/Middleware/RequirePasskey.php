<?php

namespace Hamzi\Vaultic\Http\Middleware;

use Closure;

class RequirePasskey
{
    public function handle($request, Closure $next)
    {
        $sessionKey = (string) config('vaultic.auth.session_key', 'vaultic.passkeys.authenticated');

        if (! $request->session()->get($sessionKey, false)) {
            abort(403, 'Passkey authentication is required for this route.');
        }

        return $next($request);
    }
}
