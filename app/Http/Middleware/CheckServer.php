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

use Auth;
use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckServer
{
    /**
     * The elquent model for the server.
     *
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * The request object.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::user()) {
            throw new AuthenticationException();
        }

        $this->request = $request;
        $this->server = Server::byUuid($request->route()->server);

        if (! $this->exists()) {
            return response()->view('errors.404', [], 404);
        }

        if ($this->suspended()) {
            return response()->view('errors.suspended', [], 403);
        }

        if (! $this->installed()) {
            return response()->view('errors.installing', [], 403);
        }

        return $next($request);
    }

    /**
     * Determine if the server was found on the system.
     *
     * @return bool
     */
    protected function exists()
    {
        if (! $this->server) {
            if ($this->request->expectsJson() || $this->request->is(...config('pterodactyl.json_routes'))) {
                throw new NotFoundHttpException('The requested server was not found on the system.');
            }
        }

        return (! $this->server) ? false : true;
    }

    /**
     * Determine if the server is suspended.
     *
     * @return bool
     */
    protected function suspended()
    {
        if ($this->server->suspended) {
            if ($this->request->expectsJson() || $this->request->is(...config('pterodactyl.json_routes'))) {
                throw new AccessDeniedHttpException('Server is suspended.');
            }
        }

        return $this->server->suspended;
    }

    /**
     * Determine if the server is installed.
     *
     * @return bool
     */
    protected function installed()
    {
        if ($this->server->installed !== 1) {
            if ($this->request->expectsJson() || $this->request->is(...config('pterodactyl.json_routes'))) {
                throw new AccessDeniedHttpException('Server is completing install process.');
            }
        }

        return $this->server->installed === 1;
    }
}
