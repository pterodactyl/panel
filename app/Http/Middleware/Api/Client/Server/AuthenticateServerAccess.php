<?php

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pterodactyl\Exceptions\Http\Server\ServerTransferringException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AuthenticateServerAccess
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * Routes that this middleware should not apply to if the user is an admin.
     *
     * @var string[]
     */
    protected $except = [
        'api:client:server.ws',
    ];

    /**
     * AuthenticateServerAccess constructor.
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Authenticate that this server exists and is not suspended or marked as installing.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = $request->user();
        $server = $request->route()->parameter('server');

        if (!$server instanceof Server) {
            throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
        }

        // At the very least, ensure that the user trying to make this request is the
        // server owner, a subuser, or a root admin. We'll leave it up to the controllers
        // to authenticate more detailed permissions if needed.
        if ($user->id !== $server->owner_id && !$user->root_admin) {
            // Check for subuser status.
            if (!$server->subusers->contains('user_id', $user->id)) {
                throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
            }
        }

        if ($server->suspended && !$request->routeIs('api:client:server.resources')) {
            throw new BadRequestHttpException('This server is currently suspended and the functionality requested is unavailable.');
        }

        // Still allow users to get information about their server if it is installing or being transferred.
        if (!$request->routeIs('api:client:server.view')) {
            if (!$server->isInstalled()) {
                // Throw an exception for all server routes; however if the user is an admin and requesting the
                // server details, don't throw the exception for them.
                if (!$user->root_admin || ($user->root_admin && !$request->routeIs($this->except))) {
                    throw new ConflictHttpException('Server has not completed the installation process.');
                }
            }

            if (!is_null($server->transfer)) {
                if (!$user->root_admin || ($user->root_admin && !$request->routeIs($this->except))) {
                    throw new ServerTransferringException();
                }
            }
        }

        $request->attributes->set('server', $server);

        return $next($request);
    }
}
