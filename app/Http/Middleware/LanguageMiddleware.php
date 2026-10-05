<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Support\JsonValueGuard;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    /**
     * LanguageMiddleware constructor.
     */
    public function __construct(private readonly Application $app) {}

    /**
     * Handle an incoming request and set the user's preferred language.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $this->app->setLocale($user instanceof User ? $user->language : JsonValueGuard::string(config('app.locale', 'en')));

        return $next($request);
    }
}
