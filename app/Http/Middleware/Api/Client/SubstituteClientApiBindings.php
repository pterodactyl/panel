<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Pterodactyl\Models\Server;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubstituteClientApiBindings
{
    protected Registrar $router;

    public function __construct(Registrar $router)
    {
        $this->router = $router;
    }

    /**
     * Perform substitution of route parameters for the Client API.
     *
     * @param \Illuminate\Http\Request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->router->bind('server', function ($value) {
            return Server::query()->where(Str::length($value) === 8 ? 'uuidShort' : 'uuid', $value)->firstOrFail();
        });

        $this->router->bind('allocation', function ($value, $route) {
            return $this->server($route)->allocations()->where('id', $value)->firstOrFail();
        });

        $this->router->bind('schedule', function ($value, $route) {
            return $this->server($route)->schedule()->where('id', $value)->firstOrFail();
        });

        $this->router->bind('database', function ($value, $route) {
            $id = Container::getInstance()->make(HashidsInterface::class)->decodeFirst($value);

            return $this->server($route)->where('id', $id)->firstOrFail();
        });

        $this->router->bind('backup', function ($value, $route) {
            return $this->server($route)->backups()->where('uuid', $value)->firstOrFail();
        });

        $this->router->bind('subuser', function ($value, $route) {
            return $this->server($route)->subusers()
                ->select('subusers.*')
                ->join('users', 'subusers.user_id', '=', 'users.id')
                ->where('users.uuid', $value)
                ->firstOrFail();
        });

        try {
            /** @var \Illuminate\Routing\Route $route */
            $this->router->substituteBindings($route = $request->route());
        } catch (ModelNotFoundException $exception) {
            if (isset($route) && $route->getMissing()) {
                $route->getMissing()($request);
            }

            throw $exception;
        }

        return $next($request);
    }

    /**
     * Plucks the server model off the route. If no server model is present a
     * ModelNotFound exception will be thrown.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    private function server(Route $route): Server
    {
        $server = $route->parameter('server');
        if (!$server instanceof Server) {
            throw (new ModelNotFoundException())->setModel(Server::class, [$route->parameter('server')]);
        }

        return $server;
    }
}
