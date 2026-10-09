<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMiddleware
{
    /**
     * Handle an incoming request.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $server = $request->attributes->get('server');
        throw_unless($server instanceof Server, InvalidArgumentException::class, 'The maintenance middleware requires a server request attribute.');

        $node = $server->getRelation('node');
        throw_unless($node instanceof Node, InvalidArgumentException::class, 'The maintenance middleware requires the server node relation.');

        if ($node->maintenance_mode) {
            return response()->view('errors.maintenance');
        }

        return $next($request);
    }
}
