<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Middleware\Server;

use Closure;
use Illuminate\Contracts\Session\Session;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubuserAccess
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
     * Determine if a user has permission to access a subuser.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
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
