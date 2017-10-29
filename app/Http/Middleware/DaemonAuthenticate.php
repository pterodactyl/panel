<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class DaemonAuthenticate
{
    /**
     * An array of route names to not apply this middleware to.
     *
     * @var array
     */
    private $except = [
        'daemon.configuration',
    ];

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * Create a new filter instance.
     *
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(NodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        if (! $request->header('X-Access-Node')) {
            throw new HttpException(403);
        }

        try {
            $node = $this->repository->findWhere(['daemonSecret' => $request->header('X-Access-Node')]);
        } catch (RecordNotFoundException $exception) {
            throw new HttpException(401);
        }

        $request->attributes->set('node', $node);

        return $next($request);
    }
}
