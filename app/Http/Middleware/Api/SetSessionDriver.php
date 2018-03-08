<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Barryvdh\Debugbar\LaravelDebugbar;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SetSessionDriver
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $app;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * SetSessionDriver constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Contracts\Config\Repository      $config
     */
    public function __construct(Application $app, ConfigRepository $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Set the session for API calls to only last for the one request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->app->environment() !== 'production') {
            $this->app->make(LaravelDebugbar::class)->disable();
        }

        $this->config->set('session.driver', 'array');

        return $next($request);
    }
}
