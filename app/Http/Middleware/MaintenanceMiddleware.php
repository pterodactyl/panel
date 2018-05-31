<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;

class MaintenanceMiddleware
{
    /**
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    private $response;

    /**
     * MaintenanceMiddleware constructor.
     *
     * @param \Illuminate\Contracts\Routing\ResponseFactory $response
     */
    public function __construct(ResponseFactory $response)
    {
        $this->response = $response;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = $request->attributes->get('server');
        $node = $server->getRelation('node');

        if ($node->maintenance_mode) {
            return $this->response->view('errors.maintenance');
        }

        return $next($request);
    }
}
