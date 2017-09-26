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
use Illuminate\Contracts\Session\Session;
use Illuminate\Auth\AuthenticationException;
use Pterodactyl\Services\Servers\ServerAccessHelperService;
use Pterodactyl\Exceptions\Service\Server\UserNotLinkedToServerException;

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
