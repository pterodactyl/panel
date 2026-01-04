<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Models\ApiKey;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RequireClientApiKey
{
    /**
     * Blocks a request to the Client API endpoints if the user is providing an API token
     * that was created for the application API.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof ApiKey && $token->key_type === ApiKey::TYPE_APPLICATION) { // @phpstan-ignore instanceof.alwaysTrue
            throw new AccessDeniedHttpException('You are attempting to use an application API key on an endpoint that requires a client API key.');
        }

        return $next($request);
    }
}
