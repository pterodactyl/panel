<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Application;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateApplicationUser
{
    /**
     * Authenticate that the currently authenticated user is an administrator
     * and should be allowed to proceed through the application API.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        throw_if(! $user || ! $user->root_admin, AccessDeniedHttpException::class, 'This account does not have permission to access the API.');

        return $next($request);
    }
}
