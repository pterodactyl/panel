<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SetSessionDriver
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * SetSessionDriver constructor.
     */
    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
    }

    /**
     * Set the session for API calls to only last for the one request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->config->set('session.driver', 'array');

        return $next($request);
    }
}
