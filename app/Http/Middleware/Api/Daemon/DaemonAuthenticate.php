<?php

namespace App\Http\Middleware\Api\Daemon;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Contracts\Repository\NodeRepositoryInterface;
use App\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DaemonAuthenticate
{
    /**
     * @var \App\Contracts\Repository\NodeRepositoryInterface
     */
    private $repository;

    /**
     * Daemon routes that this middleware should be skipped on.
     *
     * @var array
     */
    protected $except = [
        'daemon.configuration',
    ];

    /**
     * DaemonAuthenticate constructor.
     *
     * @param \App\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(NodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Check if a request from the daemon can be properly attributed back to a single node instance.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if (is_null($token)) {
            throw new HttpException(401, null, null, ['WWW-Authenticate' => 'Bearer']);
        }

        try {
            $node = $this->repository->findFirstWhere([['daemonSecret', '=', $token]]);
        } catch (RecordNotFoundException $exception) {
            throw new AccessDeniedHttpException;
        }

        $request->attributes->set('node', $node);

        return $next($request);
    }
}
