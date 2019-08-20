<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Contracts\Repository\NodeRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
     * @var \App\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * Create a new filter instance.
     *
     * @param \App\Contracts\Repository\NodeRepositoryInterface $repository
     * @deprecated
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
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        if (! $request->header('X-Access-Node')) {
            throw new AccessDeniedHttpException;
        }

        $node = $this->repository->findFirstWhere(['daemonSecret' => $request->header('X-Access-Node')]);
        $request->attributes->set('node', $node);

        return $next($request);
    }
}
