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
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class SubuserAccessAuthenticate
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    protected $keyProviderService;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * SubuserAccessAuthenticate constructor.
     *
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService $keyProviderService
     * @param \Illuminate\Contracts\Session\Session                     $session
     */
    public function __construct(
        DaemonKeyProviderService $keyProviderService,
        Session $session
    ) {
        $this->keyProviderService = $keyProviderService;
        $this->session = $session;
    }

    /**
     * Determine if a subuser has permissions to access a server, if so set thier access token.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $this->session->get('server_data.model');

        try {
            $token = $this->keyProviderService->handle($server->id, $request->user()->id);
            $this->session->now('server_data.token', $token);
            $request->attributes->set('server_token', $token);
        } catch (RecordNotFoundException $exception) {
            throw new AuthenticationException('This account does not have permission to access this server.');
        }

        return $next($request);
    }
}
