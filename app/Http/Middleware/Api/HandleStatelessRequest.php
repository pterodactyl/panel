<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;

class HandleStatelessRequest
{
    /**
     * Ensure that the 'Set-Cookie' header is removed from the response if
     * a bearer token is present and there is an api_key in the request attributes.
     *
     * This will also delete the session from the database automatically so that
     * it is effectively treated as a stateless request. Any additional requests
     * attempting to use that session will find no data.
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        if (!is_null($request->bearerToken()) && $request->isJson()) {
            $request->session()->getHandler()->destroy(
                $request->session()->getId()
            );

            $response->headers->remove('Set-Cookie');
        }

        return $response;
    }
}
