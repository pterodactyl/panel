<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Pterodactyl\Models\Server;
use Illuminate\Routing\Middleware\SubstituteBindings;

class SubstituteClientBindings extends SubstituteBindings
{
    /**
     * @param \Illuminate\Http\Request $request
     */
    public function handle($request, \Closure $next): mixed
    {
        // Override default behavior of the model binding to use a specific table
        // column rather than the default 'id'.
        $this->router->bind('server', function ($value) {
            return Server::query()->where(strlen($value) === 8 ? 'uuidShort' : 'uuid', $value)->firstOrFail();
        });

        $this->router->bind('user', function ($value, $route) {
            /** @var \Pterodactyl\Models\Subuser $match */
            $match = $route->parameter('server')
                ->subusers()
                ->whereRelation('user', 'uuid', '=', $value)
                ->firstOrFail();

            return $match->user;
        });

        return parent::handle($request, $next);
    }
}
