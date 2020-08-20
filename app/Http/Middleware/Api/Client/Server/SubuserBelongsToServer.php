<?php

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;

class SubuserBelongsToServer
{
    /**
     * Ensure that the user being accessed in the request is a user that is currently assigned
     * as a subuser for this server instance. We'll let the requests themselves handle wether or
     * not the user making the request can actually modify or delete the subuser record.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = $request->route()->parameter('server');
        /** @var \Pterodactyl\Models\User $user */
        $user = $request->route()->parameter('user');

        // Don't do anything if there isn't a user present in the request.
        if (is_null($user)) {
            return $next($request);
        }

        $request->attributes->set('subuser', $server->subusers()->where('user_id', $user->id)->firstOrFail());

        return $next($request);
    }
}
