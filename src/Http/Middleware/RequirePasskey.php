<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that enforces passkey-based authentication on protected routes.
 *
 * Checks for a session flag set during successful passkey authentication.
 * Returns 403 if the flag is missing.
 */
class RequirePasskey
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = (string) config('vaultic.auth.session_key', 'vaultic.passkeys.authenticated');

        if (! $request->session()->get($sessionKey, false)) {
            abort(403, 'Passkey authentication is required for this route.');
        }

        return $next($request);
    }
}
