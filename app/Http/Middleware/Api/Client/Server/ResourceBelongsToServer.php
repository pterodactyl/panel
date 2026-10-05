<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Pterodactyl\Http\Concerns\ResolvesRequestContext;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Schedule;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\Task;
use Pterodactyl\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceBelongsToServer
{
    use ResolvesRequestContext;

    /**
     * Looks at the request parameters to determine if the given resource belongs
     * to the requested server. If not, a 404 error will be returned to the caller.
     *
     * This is critical to ensuring that all subsequent logic is using exactly the
     * server that is expected, and that we're not accessing a resource completely
     * unrelated to the server provided in the request.
     */
    /**
     * @param  Closure(Request):Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $route = $this->resolvedRoute($request);
        $params = $route->parameters();
        throw_if(! isset($params['server']) || ! $params['server'] instanceof Server, InvalidArgumentException::class, 'This middleware cannot be used in a context that is missing a server in the parameters.');

        $server = $params['server'];
        $exception = new NotFoundHttpException('The requested resource was not found for this server.');
        foreach ($params as $key => $model) {
            // Specifically skip the server, we're just trying to see if all of the
            // other resources are assigned to this server. Also skip anything that
            // is not currently a Model instance since those will just end up being
            // a 404 down the road.
            if ($key === 'server' || ! $model instanceof Model) {
                continue;
            }

            switch ($model::class) {
                // All of these models use "server_id" as the field key for the server
                // they are assigned to, so the logic is identical for them all.
                case Allocation::class:
                case Backup::class:
                case Database::class:
                case Schedule::class:
                case Subuser::class:
                    throw_if($model->server_id !== $server->id, $exception);

                    break;
                    // Regular users are a special case here as we need to make sure they're
                    // currently assigned as a subuser on the server.
                case User::class:
                    $subuser = $server->subusers()->where('user_id', $model->id)->first();
                    throw_if(($subuser) === null, $exception);

                    // This is a special case to avoid an additional query being triggered
                    // in the underlying logic.
                    $request->attributes->set('subuser', $subuser);
                    break;
                    // Tasks are special since they're (currently) the only item in the API
                    // that requires something in addition to the server in order to be accessed.
                case Task::class:
                    $schedule = $route->parameter('schedule');
                    throw_if(! $schedule instanceof Schedule || $model->schedule_id !== $schedule->id || $schedule->server_id !== $server->id, $exception);

                    break;
                default:
                    // Don't return a 404 here since we want to make sure no one relies
                    // on this middleware in a context in which it will not work. Fail safe.
                    throw new InvalidArgumentException('There is no handler configured for a resource of this type: '.$model::class);
            }
        }

        return $next($request);
    }
}
