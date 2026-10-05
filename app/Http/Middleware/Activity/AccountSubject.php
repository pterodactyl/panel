<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Activity;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Facades\LogTarget;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Symfony\Component\HttpFoundation\Response;

class AccountSubject
{
    use ResolvesRequestContext;

    /**
     * Sets the actor and default subject for all requests passing through this
     * middleware to be the currently logged in user.
     *
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $this->authenticatedUser($request);
        LogTarget::setActor($user);
        LogTarget::setSubject($user);

        return $next($request);
    }
}
