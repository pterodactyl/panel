<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Illuminate\Support\Str;
use Pterodactyl\Models\Task;
use Illuminate\Routing\Route;
use Pterodactyl\Models\Server;
use Illuminate\Container\Container;
use Illuminate\Database\Query\JoinClause;
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
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->router->bind('server', function ($value) {
            return Server::query()->where(Str::length($value) === 8 ? 'uuidShort' : 'uuid', $value)->firstOrFail();
        });

        $this->router->bind('allocation', function ($value, $route) {
            return $this->server($route)->allocations()->findOrFail($value);
        });

        $this->router->bind('schedule', function ($value, $route) {
            return $this->server($route)->schedule()->findOrFail($value);
        });

        $this->router->bind('task', function ($value, $route) {
            return Task::query()
                ->select('tasks.*')
                ->join('schedules', function (JoinClause $join) use ($route) {
                    $join->on('schedules.id', 'tasks.schedule_id')
                        ->where('schedules.server_id', $route->parameter('server')->id);
                })
                ->where('schedules.id', $route->parameter('schedule')->id)
                ->findOrFail($value);
        });

        $this->router->bind('database', function ($value, $route) {
            $id = Container::getInstance()->make(HashidsInterface::class)->decodeFirst($value);

            return $this->server($route)->databases()->findOrFail($id);
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
            /* @var \Illuminate\Routing\Route $route */
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
