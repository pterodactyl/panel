<?php

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AllocationBelongsToServer
{
    /**
     * Ensure that the allocation found in the URL belongs to the server being queried.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = $request->route()->parameter('server');
        /** @var \Pterodactyl\Models\Allocation|null $allocation */
        $allocation = $request->route()->parameter('allocation');

        if ($allocation && $allocation->server_id !== $server->id) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
