<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\View\FileViewFinder;

class ThemeSetter
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $theme = config('theme.default');

        if ($theme) {
            $paths = \Config::get('view.paths');
            $base = resource_path('themes');

            array_unshift($paths, "$base/$theme");

            $finder = new FileViewFinder(app()['files'], $paths);
            app()['view']->setFinder($finder);
        }

        return $next($request);
    }
}
