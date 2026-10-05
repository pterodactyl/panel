<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use IPTools\IP;
use IPTools\Range;
use Laravel\Sanctum\TransientToken;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateIPAccess
{
    use ResolvesRequestContext;

    /**
     * Determine if a request IP has permission to access the API.
     *
     * @throws Exception
     * @throws AccessDeniedHttpException
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $this->authenticatedUser($request);
        // SAFETY: Sanctum may supply a TransientToken for stateful requests; authenticated API requests use the configured ApiKey model.
        /** @var TransientToken|ApiKey|null $token */
        $token = $user->currentAccessToken();

        // If this is a stateful request just push the request through to the next
        // middleware in the stack, there is nothing we need to explicitly check. If
        // this is a valid API Key, but there is no allowed IP restriction, also pass
        // the request through.
        if ($token === null || $token instanceof TransientToken || empty($token->allowed_ips)) {
            return $next($request);
        }

        $requestIp = $request->ip();
        throw_if($requestIp === null, AccessDeniedHttpException::class, 'The request did not contain a valid IP address.');

        $find = new IP($requestIp);
        foreach ($token->allowed_ips as $ip) {
            if (Range::parse($ip)->contains($find)) {
                return $next($request);
            }
        }

        Activity::event('auth:ip-blocked')
            ->actor($user)
            ->subject($user, $token)
            ->property('identifier', $token->identifier)
            ->log();

        throw new AccessDeniedHttpException('This IP address ('.$request->ip().') does not have permission to access the API using these credentials.');
    }
}
