<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * RedirectIfAuthenticated constructor.
     */
    public function __construct(private readonly AuthManager $authManager) {}

    /**
     * Handle an incoming request.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): mixed
    {
        if ($this->authManager->guard($guard)->check()) {
            return to_route('index');
        }

        return $next($request);
    }
}
