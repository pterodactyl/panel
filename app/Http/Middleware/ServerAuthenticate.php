<?php
/**
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

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ServerAuthenticate
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * ServerAuthenticate constructor.
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
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()) {
            throw new AuthenticationException;
        }

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

        if ($server->installed !== 1) {
            if ($isApiRequest) {
                throw new AccessDeniedHttpException('Server is completing install process.');
            }

            return response()->view('errors.installing', [], 403);
        }

        // Store the server in the session.
        $this->session->now('server_data.model', $server);

        return $next($request);
    }
}
