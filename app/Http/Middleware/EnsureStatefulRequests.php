<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Pterodactyl\Support\JsonValueGuard;

class EnsureStatefulRequests extends EnsureFrontendRequestsAreStateful
{
    /**
     * Treats a request as stateful when Sanctum's default "fromFrontend" check passes or the
     * request carries the Pterodactyl session cookie, since cookie-based API usage is only
     * supported for the front-end we control.
     */
    public static function fromFrontend($request): bool
    {
        if (parent::fromFrontend($request)) {
            return true;
        }

        return $request->hasCookie(JsonValueGuard::string(config('session.cookie')));
    }
}
