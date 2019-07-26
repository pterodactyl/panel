<?php

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateServerAccess
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * AuthenticateServerAccess constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Authenticate that this server exists and is not suspended or marked as installing.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->route()->parameter('server');

        if (! $server instanceof Server) {
            throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
        }

        if ($server->suspended) {
            throw new AccessDeniedHttpException('Cannot access a server that is marked as being suspended.');
        }

        if (! $server->isInstalled()) {
            throw new ConflictHttpException('Server has not completed the installation process.');
        }

        $request->attributes->set('server', $server);

        return $next($request);
    }
}
