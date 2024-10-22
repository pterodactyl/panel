<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        if (!$request->user() || !$request->user()->root_admin) {
            throw new AccessDeniedHttpException();
        }

        return $next($request);
    }
}
