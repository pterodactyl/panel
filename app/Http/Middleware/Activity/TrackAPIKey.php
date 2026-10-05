<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Activity;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Facades\LogTarget;
use Pterodactyl\Models\ApiKey;
use Symfony\Component\HttpFoundation\Response;

class TrackAPIKey
{
    /**
     * Determines if the authenticated user making this request is using an actual
     * API key, or it is just a cookie authenticated session. This data is set in a
     * request singleton so that all tracked activity log events are properly associated
     * with the given API key.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()) {
            $token = $request->user()->currentAccessToken();

            $id = $token instanceof ApiKey ? $token->id : null;

            LogTarget::setApiKeyId($id !== null && $id > 0 ? $id : null);
        }

        return $next($request);
    }
}
