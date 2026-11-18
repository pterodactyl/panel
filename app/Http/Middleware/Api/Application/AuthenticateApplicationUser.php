<?php

namespace Pterodactyl\Http\Middleware\Api\Application;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateApplicationUser
{
    /**
     * Authenticate that the currently authenticated user is an administrator
     * and should be allowed to proceed through the application API.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        /** @var \Pterodactyl\Models\User|null $user */
        $user = $request->user();
        if (!$user || !$user->root_admin) {
            throw new AccessDeniedHttpException('This account does not have permission to access the API.');
        }

        // Client API keys with permission or server restrictions are scoped to a
        // subset of the client API; they may never be used against the application
        // API, which would otherwise expose every server and user on the system.
        if ($user->currentApiKey()?->isRestricted()) {
            throw new AccessDeniedHttpException('This API key is restricted and may not be used to access the application API.');
        }

        return $next($request);
    }
}
