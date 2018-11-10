<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthenticateClientAccess
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService
     */
    private $keyProviderService;

    /**
     * AuthenticateClientAccess constructor.
     *
     * @param \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService $keyProviderService
     */
    public function __construct(DaemonKeyProviderService $keyProviderService)
    {
        $this->keyProviderService = $keyProviderService;
    }

    /**
     * Authenticate that the currently authenticated user has permission
     * to access the specified server. This only checks that the user is an
     * admin, owner, or a subuser. You'll need to do more specific checks in
     * the API calls to determine if they can perform different actions.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(Request $request, Closure $next)
    {
        if (is_null($request->user())) {
            throw new AccessDeniedHttpException('A request must be made using an authenticated client.');
        }

        /** @var \Pterodactyl\Models\Server $server */
        $server = $request->route()->parameter('server');

        try {
            $token = $this->keyProviderService->handle($server, $request->user());
        } catch (RecordNotFoundException $exception) {
            throw new NotFoundHttpException('The requested server could not be located.');
        }

        $request->attributes->set('server_token', $token);

        return $next($request);
    }
}
