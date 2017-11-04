<?php

namespace Pterodactyl\Http\Middleware\Server;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubuserBelongsToServer
{
    /**
     * @var \Pterodactyl\Contracts\Extensions\HashidsInterface
     */
    private $hashids;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    private $repository;

    /**
     * SubuserAccess constructor.
     *
     * @param \Pterodactyl\Contracts\Extensions\HashidsInterface           $hashids
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface $repository
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
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
