<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Config\Repository;

class LanguageMiddleware
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    private $app;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * LanguageMiddleware constructor.
     *
     * @param \Illuminate\Foundation\Application      $app
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Application $app, Repository $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->app->setLocale($this->config->get('app.locale', 'en'));

        return $next($request);
    }
}
