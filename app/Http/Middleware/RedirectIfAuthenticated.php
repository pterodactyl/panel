<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;

class RedirectIfAuthenticated
{
    /**
     * @var \Illuminate\Auth\AuthManager
     */
    private $authManager;

    /**
     * RedirectIfAuthenticated constructor.
     */
    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $guard = null)
    {
        if ($this->authManager->guard($guard)->check()) {
            return redirect()->route('index');
        }

        return $next($request);
    }
}
