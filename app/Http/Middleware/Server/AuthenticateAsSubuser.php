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
use Illuminate\Http\Request;
use Illuminate\Contracts\Session\Session;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateAsSubuser
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    private $session;

    /**
     * SubuserAccessAuthenticate constructor.
     *
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService $keyProviderService
     * @param \Illuminate\Contracts\Session\Session                     $session
     */
    public function __construct(DaemonKeyProviderService $keyProviderService, Session $session)
    {
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
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->attributes->get('server');

        try {
            $token = $this->keyProviderService->handle($server, $request->user());
        } catch (RecordNotFoundException $exception) {
            throw new AccessDeniedHttpException('This account does not have permission to access this server.');
        }

        $this->session->now('server_data.token', $token);
        $request->attributes->set('server_token', $token);

        return $next($request);
    }
}
