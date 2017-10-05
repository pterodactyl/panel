<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware;

use Auth;
use Closure;
use Session;
use Settings;
use Illuminate\Support\Facades\App;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // if (Session::has('applocale')) {
        //     App::setLocale(Session::get('applocale'));
        // } elseif (Auth::check() && isset(Auth::user()->language)) {
        //     Session::put('applocale', Auth::user()->language);
        //     App::setLocale(Auth::user()->language);
        // } else {
        //     App::setLocale(Settings::get('default_language', 'en'));
        // }
        App::setLocale('en');

        return $next($request);
    }
}
