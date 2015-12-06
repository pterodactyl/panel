<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Debugbar;

use Pterodactyl\Models\API;

class APIAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if(!$request->header('X-Authorization')) {
            return response()->json([
                'error' => 'Authorization header was missing with this request. Please pass the \'X-Authorization\' header with your request.'
            ], 403);
        }

        $api = API::where('key', $request->header('X-Authorization'))->first();
        if (!$api) {
            return response()->json([
                'error' => 'Invalid API key was provided in the request.'
            ], 403);
        }

        if (!is_null($api->allowed_ips)) {
            if (!in_array($request->ip(), json_decode($api->allowed_ips, true))) {
                return response()->json([
                    'error' => 'This IP (' . $request->ip() . ') is not permitted to access the API with that token.'
                ], 403);
            }
        }

        return $next($request);

    }
}
