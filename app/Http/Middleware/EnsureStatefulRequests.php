<?php

namespace Pterodactyl\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class EnsureStatefulRequests extends EnsureFrontendRequestsAreStateful
{
    /**
     * Determines if a request is stateful or not. This is determined using the default
     * Sanctum "fromFrontend" helper method. However, we also check if the request includes
     * a cookie value for the Pterodactyl session. If so, we assume this is a stateful
     * request.
     *
     * We don't want to support API usage using the cookies, except for requests stemming
     * from the front-end we control.
     */
    public static function fromFrontend($request)
    {
        if (parent::fromFrontend($request)) {
            return true;
        }

        return $request->hasCookie(config('session.cookie'));
    }
}
