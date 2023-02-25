<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->isSuspended()) {
            auth()->logout();

            $message = 'Your account has been suspended. Please contact our support team!';

            return redirect()->route('login')->withMessage($message);
        }

        return $next($request);
    }
}
