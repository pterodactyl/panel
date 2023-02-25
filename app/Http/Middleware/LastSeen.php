<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (env('APP_ENV', 'local') == 'local') {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        if ($request->session()->has('previousUser')) {
            return $next($request);
        }

        Auth::user()->update([
            'last_seen' => now(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
