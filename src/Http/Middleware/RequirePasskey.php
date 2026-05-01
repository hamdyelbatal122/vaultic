<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePasskey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('vaultic.passkeys.authenticated', false)) {
            abort(403, 'Passkey authentication is required for this route.');
        }

        return $next($request);
    }
}
