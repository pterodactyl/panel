<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Activity;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Facades\LogTarget;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpFoundation\Response;

class ServerSubject
{
    use ResolvesRequestContext;

    /**
     * Attempts to automatically scope all of the activity log events registered
     * within the request instance to the given user and server. This only sets
     * the actor and subject if there is a server present on the request.
     *
     * If no server is found this is a no-op as the activity log service can always
     * set the user based on the authmanager response.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $server = $this->resolvedRoute($request)->parameter('server');
        if ($server instanceof Server) {
            LogTarget::setActor($this->authenticatedUser($request));
            LogTarget::setSubject($server);
        }

        return $next($request);
    }
}
