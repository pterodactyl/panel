<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace App\Http\Middleware\Server;

use Closure;
use Illuminate\Http\Request;
use App\Services\DaemonKeys\DaemonKeyProviderService;
use App\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateAsSubuser
{
    /**
     * @var \App\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * SubuserAccessAuthenticate constructor.
     *
     * @param \App\Services\DaemonKeys\DaemonKeyProviderService $keyProviderService
     */
    public function __construct(DaemonKeyProviderService $keyProviderService)
    {
        $this->keyProviderService = $keyProviderService;
    }

    /**
     * Determine if a subuser has permissions to access a server, if so set their access token.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \App\Exceptions\Model\DataValidationException
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

        $request->attributes->set('server_token', $token);

        return $next($request);
    }
}
