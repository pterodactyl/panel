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

        if (!Server::getByUUID($request->route()->server)) {
            return redirect('/');
        }

        return $next($request);

    }
}
