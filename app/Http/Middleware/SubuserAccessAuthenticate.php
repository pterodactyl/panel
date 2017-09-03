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

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Pterodactyl\Exceptions\Service\Server\UserNotLinkedToServerException;
use Pterodactyl\Services\Servers\ServerAccessHelperService;

class SubuserAccessAuthenticate
{
    /**
     * @var \Pterodactyl\Services\Servers\ServerAccessHelperService
     */
    protected $accessHelperService;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * SubuserAccessAuthenticate constructor.
     *
     * @param \Pterodactyl\Services\Servers\ServerAccessHelperService $accessHelperService
     * @param \Illuminate\Contracts\Session\Session                   $session
     */
    public function __construct(
        ServerAccessHelperService $accessHelperService,
        Session $session
    ) {
        $this->accessHelperService = $accessHelperService;
        $this->session = $session;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $this->session->get('server_data.model');

        try {
            $token = $this->accessHelperService->handle($server, $request->user());
            $this->session->now('server_data.token', $token);
        } catch (UserNotLinkedToServerException $exception) {
            throw new AuthenticationException('This account does not have permission to access this server.');
        }

        return $next($request);
    }
}
