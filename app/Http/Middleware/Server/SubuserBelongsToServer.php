<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware\Server;

use Closure;
use Illuminate\Contracts\Session\Session;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubuserBelongsToServer
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * SubuserAccess constructor.
     *
     * @param \Illuminate\Contracts\Session\Session                        $session
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface $repository
     */
    public function __construct(Session $session, SubuserRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->session = $session;
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
    public function handle($request, Closure $next)
    {
        $server = $this->session->get('server_data.model');

        $subuser = $this->repository->find($request->route()->parameter('subuser', 0));
        if ($subuser->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        if ($request->method() === 'PATCH') {
            if ($subuser->user_id === $request->user()->id) {
                throw new DisplayException(trans('exceptions.subusers.editing_self'));
            }
        }

        return $next($request);
    }
}
