<?php

namespace Pterodactyl\Http\Middleware\Api\Application;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateApplicationUser
{
    /**
     * Authenticate that the currently authenticated user is an administrator
     * and should be allowed to proceed through the application API.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_null($request->user()) || !$request->user()->root_admin) {
            throw new AccessDeniedHttpException('This account does not have permission to access the API.');
        }

        return $next($request);
    }
}
