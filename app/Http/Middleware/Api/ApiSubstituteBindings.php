<?php

namespace Pterodactyl\Http\Middleware\Api;

use Closure;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiSubstituteBindings extends SubstituteBindings
{
    /**
     * Mappings to automatically assign route parameters to a model.
     *
     * @var array
     */
    protected static $mappings = [
        'allocation' => Allocation::class,
        'database' => Database::class,
        'egg' => Egg::class,
        'location' => Location::class,
        'nest' => Nest::class,
        'node' => Node::class,
        'server' => Server::class,
        'user' => User::class,
    ];

    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

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
        $route = $request->route();

        foreach (self::$mappings as $key => $model) {
            if (!is_null($this->router->getBindingCallback($key))) {
                continue;
            }

            $this->router->model($key, $model, function () use ($request) {
                $request->attributes->set('is_missing_model', true);
            });
        }

        $this->router->substituteBindings($route);

        // Attempt to resolve bindings for this route. If one of the models
        // cannot be resolved do not immediately return a 404 error. Set a request
        // attribute that can be checked in the base API request class to only
        // trigger a 404 after validating that the API key making the request is valid
        // and even has permission to access the requested resource.
        try {
            $this->router->substituteImplicitBindings($route);
        } catch (ModelNotFoundException $exception) {
            $request->attributes->set('is_missing_model', true);
        }

        return $next($request);
    }

    /**
     * Return the registered mappings.
     *
     * @return array
     */
    public static function getMappings()
    {
        return self::$mappings;
    }
}
