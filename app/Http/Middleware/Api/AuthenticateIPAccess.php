<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use IPTools\IP;
use IPTools\Range;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateIPAccess
{
    /**
     * Determine if a request IP has permission to access the API.
     *
     * @return mixed
     *
     * @throws \Exception
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $model = $request->attributes->get('api_key');

        if (is_null($model->allowed_ips) || empty($model->allowed_ips)) {
            return $next($request);
        }

        $find = new IP($request->ip());
        foreach ($model->allowed_ips as $ip) {
            if (Range::parse($ip)->contains($find)) {
                return $next($request);
            }
        }

        throw new AccessDeniedHttpException('This IP address (' . $request->ip() . ') does not have permission to access the API using these credentials.');
    }
}
