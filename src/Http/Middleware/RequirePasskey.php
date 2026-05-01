<?php

namespace Hamzi\Vaultic\Http\Middleware;

use Closure;

class RequirePasskey
{
    public function handle($request, Closure $next)
    {
        if (! $request->session()->get('vaultic.passkeys.authenticated', false)) {
            abort(403, 'Passkey authentication is required for this route.');
        }

        return $next($request);
    }
}
