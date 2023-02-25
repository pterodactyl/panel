<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GlobalNames
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
        define('CREDITS_DISPLAY_NAME', config('SETTINGS::SYSTEM:CREDITS_DISPLAY_NAME', 'Credits'));

        $unsupported_lang_array = explode(',', config('app.unsupported_locales'));
        $unsupported_lang_array = array_map('strtolower', $unsupported_lang_array);
        define('UNSUPPORTED_LANGS', $unsupported_lang_array);

        return $next($request);
    }
}
