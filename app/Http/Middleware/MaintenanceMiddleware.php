<?php

namespace Pterodactyl\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Contracts\Routing\ResponseFactory;

class MaintenanceMiddleware
{
    /**
     * MaintenanceMiddleware constructor.
     */
    public function __construct(private ResponseFactory $response)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): mixed
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
