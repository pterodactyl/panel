<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\TransientToken;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RequireClientApiKey
{
    use ResolvesRequestContext;

    /**
     * Blocks a request to the Client API endpoints if the user is providing an API token
     * that was created for the application API.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $this->authenticatedUser($request)->currentAccessToken();
        if (! $token instanceof HasAbilities || $token instanceof TransientToken) {
            return $next($request);
        }

        throw_if($token->key_type === ApiKey::TYPE_APPLICATION, AccessDeniedHttpException::class, 'You are attempting to use an application API key on an endpoint that requires a client API key.');

        return $next($request);
    }
}
