<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Auth;
use Pterodactyl\Models\Server;
use Debugbar;

class CheckServer
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

        if (!Auth::user()) {
            return redirect()->guest('auth/login');
        }

        $server = Server::getByUUID($request->route()->server);
        if (!$server) {
            return response()->view('errors.403', [], 403);
        }

        if ($server->installed !== 1) {
            return response()->view('errors.installing', [], 503);
        }

        return $next($request);

    }
}
