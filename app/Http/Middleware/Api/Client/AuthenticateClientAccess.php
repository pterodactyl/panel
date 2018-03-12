<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateClientAccess
{
    /**
     * Authenticate that the currently authenticated user has permission
     * to access the specified server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_null($request->user())) {
            throw new AccessDeniedHttpException('This account does not have permission to access this resource.');
        }

        return $next($request);
    }
}
