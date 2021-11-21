<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Pterodactyl\Models\ApiKey;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification. These are
     * never hit by the front-end, and require specific token validation
     * to work.
     *
     * @var string[]
     */
    protected $except = ['remote/*', 'daemon/*'];

    /**
     * Manually apply CSRF protection to routes depending on the authentication
     * mechanism being used. If the API request is using an API key that exists
     * in the database we can safely ignore CSRF protections, since that would be
     * a manually initiated request by a user or server.
     *
     * All other requests should go through the standard CSRF protections that
     * Laravel affords us. This code will be removed in v2 since we have switched
     * to using Sanctum for the API endpoints, which handles that for us automatically.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        $key = $request->attributes->get('api_key');

        if ($key instanceof ApiKey && $key->exists) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
