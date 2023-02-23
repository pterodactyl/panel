<?php

namespace Pterodactyl\Http\Middleware\Activity;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Facades\LogTarget;

class ServerSubject
{
    /**
     * Attempts to automatically scope all of the activity log events registered
     * within the request instance to the given user and server. This only sets
     * the actor and subject if there is a server present on the request.
     *
     * If no server is found this is a no-op as the activity log service can always
     * set the user based on the authmanager response.
     */
    public function handle(Request $request, \Closure $next)
    {
        $server = $request->route()->parameter('server');
        if ($server instanceof Server) {
            LogTarget::setActor($request->user());
            LogTarget::setSubject($server);
        }

        return $next($request);
    }
}
