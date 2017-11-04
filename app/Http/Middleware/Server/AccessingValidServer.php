<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessingValidServer
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    private $session;

    /**
     * AccessingValidServer constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                     $config
     * @param \Pterodactyl\Contracts\Repository\ServerRepositoryInterface $repository
     * @param \Illuminate\Contracts\Session\Session                       $session
     */
    public function __construct(
        ConfigRepository $config,
        ServerRepositoryInterface $repository,
        Session $session
    ) {
        $this->config = $config;
        $this->repository = $repository;
        $this->session = $session;
    }

    /**
     * Determine if a given user has permission to access a server.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return \Illuminate\Http\Response|mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $attributes = $request->route()->parameter('server');
        $isApiRequest = $request->expectsJson() || $request->is(...$this->config->get('pterodactyl.json_routes', []));
        $server = $this->repository->getByUuid($attributes instanceof Server ? $attributes->uuid : $attributes);

        if (! $server) {
            if ($isApiRequest) {
                throw new NotFoundHttpException('The requested server was not found on the system.');
            }

            return response()->view('errors.404', [], 404);
        }

        if ($server->suspended) {
            if ($isApiRequest) {
                throw new AccessDeniedHttpException('Server is suspended.');
            }

            return response()->view('errors.suspended', [], 403);
        }

        // Servers can have install statuses other than 1 or 0, so don't check
        // for a bool-type operator here.
        if ($server->installed !== 1) {
            if ($isApiRequest) {
                throw new AccessDeniedHttpException('Server is not marked as installed.');
            }

            return response()->view('errors.installing', [], 403);
        }

        // Store the server in the session.
        // @todo remove from session. use request attributes.
        $this->session->now('server_data.model', $server);

        // Add server to the request attributes. This will replace sessions
        // as files are updated.
        $request->attributes->set('server', $server);

        return $next($request);
    }
}
