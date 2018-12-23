<?php

namespace Pterodactyl\Http\Middleware\Server;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DatabaseBelongsToServer
{
    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseAccess constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface $repository
     */
    public function __construct(DatabaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Check if a database being requested belongs to the currently loaded server.
     * If it does not, throw a 404 error, otherwise continue on with the request
     * and set an attribute with the database.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->attributes->get('server');
        $database = $request->input('database') ?? $request->route()->parameter('database');

        if (! is_digit($database)) {
            throw new NotFoundHttpException;
        }

        $database = $this->repository->find($database);
        if (is_null($database) || $database->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        $request->attributes->set('database', $database);

        return $next($request);
    }
}
