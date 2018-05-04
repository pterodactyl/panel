<?php

namespace Pterodactyl\Http\Middleware;

use Closure;

class MaintenanceMiddleware
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
        $server = $request->attributes->get('server');
        $node = $server->node;

        if ($node->maintenance) {
            return response(view('errors.maintenance'));
        }

        return $next($request);
    }
}
