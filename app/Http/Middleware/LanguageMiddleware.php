<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Translations\LanguageRegistry;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    public function __construct(private readonly Application $app, private readonly LanguageRegistry $languages) {}

    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $this->app->setLocale($this->languages->resolveFor($user instanceof User ? $user : null));

        return $next($request);
    }
}
