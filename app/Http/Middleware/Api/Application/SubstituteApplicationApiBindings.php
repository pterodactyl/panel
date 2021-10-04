<?php

namespace Pterodactyl\Http\Middleware\Api\Application;

use Closure;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubstituteApplicationApiBindings
{
    protected Registrar $router;

    /**
     * Mappings to automatically assign route parameters to a model.
     */
    protected static array $mappings = [
        'allocation' => Allocation::class,
        'database' => Database::class,
        'egg' => Egg::class,
        'location' => Location::class,
        'nest' => Nest::class,
        'node' => Node::class,
        'server' => Server::class,
        'user' => User::class,
    ];

    public function __construct(Registrar $router)
    {
        $this->router = $router;
    }

    /**
     * Perform substitution of route parameters without triggering
     * a 404 error if a model is not found.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        foreach (self::$mappings as $key => $class) {
            $this->router->bind($key, $class);
        }

        try {
            $this->router->substituteImplicitBindings($route = $request->route());
        } catch (ModelNotFoundException $exception) {
            if (isset($route) && $route->getMissing()) {
                $route->getMissing()($request);
            }

            throw $exception;
        }

        return $next($request);
    }
}
