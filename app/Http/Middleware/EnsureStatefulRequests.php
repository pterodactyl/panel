<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class EnsureStatefulRequests extends EnsureFrontendRequestsAreStateful
{
    /**
     * {@inheritDoc}
     */
    public function handle($request, $next)
    {
        $this->configureSecureCookieSessions();

        return (new Pipeline(app()))
            ->send($request)
            ->through($this->isStateful($request) ? $this->statefulMiddleware() : [])
            ->then(fn ($request) => $next($request));
    }

    /**
     * Determines if a request is stateful or not. This is determined using the default
     * Sanctum "fromFrontend" helper method. However, we also check if the request includes
     * a cookie value for the Pterodactyl session. If so, we assume this is a stateful
     * request.
     *
     * We don't want to support API usage using the cookies, except for requests stemming
     * from the front-end we control.
     */
    protected function isStateful(Request $request): bool
    {
        return static::fromFrontend($request) || $request->hasCookie(config('session.cookie'));
    }

    /**
     * Returns the middleware to be applied to a stateful request to the API.
     */
    protected function statefulMiddleware(): array
    {
        return [
            function ($request, $next) {
                $request->attributes->set('sanctum', true);

                return $next($request);
            },
            config('sanctum.middleware.encrypt_cookies', EncryptCookies::class),
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            config('sanctum.middleware.verify_csrf_token', VerifyCsrfToken::class),
        ];
    }
}
