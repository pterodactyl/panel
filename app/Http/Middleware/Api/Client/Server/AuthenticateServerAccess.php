<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\Http\Server\ServerStateConflictException;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthenticateServerAccess
{
    use ResolvesRequestContext;

    /**
     * Routes that this middleware should not apply to if the user is an admin.
     *
     * @var list<string>
     */
    protected array $except = [
        'api:client:server.ws',
    ];

    /**
     * Authenticate that this server exists and is not suspended or marked as installing.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $this->authenticatedUser($request);
        $server = $this->resolvedRoute($request)->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
        }

        // At the very least, ensure that the user trying to make this request is the
        // server owner, a subuser, or a root admin. We'll leave it up to the controllers
        // to authenticate more detailed permissions if needed.
        if ($user->id !== $server->owner_id && ! $user->root_admin) {
            // Check for subuser status.
            if ($server->subusers->doesntContain('user_id', $user->id)) {
                throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
            }
        }

        try {
            $server->validateCurrentState();
        } catch (ServerStateConflictException $serverStateConflictException) {
            // Still allow users to get information about their server if it is installing or
            // being transferred.
            if (! $request->routeIs('api:client:server.view')) {
                throw_if(($server->isSuspended() || $server->node->isUnderMaintenance()) && ! $request->routeIs('api:client:server.resources'), $serverStateConflictException);
                throw_if(! $user->root_admin || ! $request->routeIs($this->except), $serverStateConflictException);
            }
        }

        $request->attributes->set('server', $server);

        return $next($request);
    }
}
