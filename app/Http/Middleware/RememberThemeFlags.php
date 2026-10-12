<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Services\Themes\ThemeResolver;
use Symfony\Component\HttpFoundation\Response;

/**
 * `?safe_mode=1` renders this session with the classic theme and no extension bundles, and
 * `?safe_mode=0` turns it off again.
 */
class RememberThemeFlags
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->hasSession()) {
            $this->remember($request, 'safe_mode', ThemeResolver::SAFE_MODE);
        }

        return $next($request);
    }

    private function remember(Request $request, string $parameter, string $key): void
    {
        if (! $request->query->has($parameter)) {
            return;
        }

        if ($request->query->getBoolean($parameter)) {
            $request->session()->put($key, true);
        } else {
            $request->session()->forget($key);
        }
    }
}
