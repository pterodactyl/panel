<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateServerParameterAccess
{
    use ResolvesRequestContext;

    public function __construct(
        private AuthenticateServerAccess $access,
        private ResourceBelongsToServer $resources,
    ) {}

    /**
     * Run the server routes' access checks on a route outside /api/client/servers/{server}
     * that still declares a {server} parameter, so it never receives a server the user
     * cannot access. Routes without that parameter pass straight through.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array('server', $this->resolvedRoute($request)->parameterNames(), true)) {
            return $next($request);
        }

        return $this->access->handle($request, fn (Request $request): Response => $this->resources->handle($request, $next));
    }
}
