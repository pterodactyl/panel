<?php

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IsMinecraft
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $server = $request->attributes->get('server');

        $nest_id = $server->nest_id;
        $minecraft_nest_ids = [1];

        if (!in_array($nest_id, $minecraft_nest_ids)) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
