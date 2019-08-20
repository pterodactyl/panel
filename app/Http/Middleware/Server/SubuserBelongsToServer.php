<?php

namespace App\Http\Middleware\Server;

use Closure;
use Illuminate\Http\Request;
use App\Exceptions\DisplayException;
use App\Contracts\Extensions\HashidsInterface;
use App\Contracts\Repository\SubuserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubuserBelongsToServer
{
    /**
     * @var \App\Contracts\Extensions\HashidsInterface
     */
    private $hashids;

    /**
     * @var \App\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * SubuserAccess constructor.
     *
     * @param \App\Contracts\Extensions\HashidsInterface           $hashids
     * @param \App\Contracts\Repository\SubuserRepositoryInterface $repository
     */
    public function __construct(HashidsInterface $hashids, SubuserRepositoryInterface $repository)
    {
        $this->hashids = $hashids;
        $this->repository = $repository;
    }

    /**
     * Determine if a user has permission to access and modify subuser.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \App\Exceptions\DisplayException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->attributes->get('server');

        $hash = $request->route()->parameter('subuser', 0);
        $subuser = $this->repository->find($this->hashids->decodeFirst($hash, 0));
        if (is_null($subuser) || $subuser->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        if ($request->method() === 'PATCH') {
            if ($subuser->user_id === $request->user()->id) {
                throw new DisplayException(trans('exceptions.subusers.editing_self'));
            }
        }

        $request->attributes->set('subuser', $subuser);

        return $next($request);
    }
}
