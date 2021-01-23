<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;

class LanguageMiddleware
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    private $app;

    /**
     * LanguageMiddleware constructor.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request and set the user's preferred language.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->app->setLocale($request->user()->language ?? config('app.locale', 'en'));

        return $next($request);
    }
}
