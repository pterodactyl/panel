<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Route;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class SubstituteClientBindings extends SubstituteBindings
{
    /**
     * @param  Request  $request
     */
    /**
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @return Response
     */
    public function handle($request, Closure $next): mixed
    {
        // Override default behavior of the model binding to use a specific table
        // column rather than the default 'id'.
        $this->router->bind('server', fn (string $value) => Server::query()
            ->when(
                str_starts_with($value, 'serv_'),
                fn (Builder $builder) => $builder->whereIdentifier($value),
                fn (Builder $builder) => $builder->where(mb_strlen($value) === 8 ? 'uuidShort' : 'uuid', $value)
            )
            ->firstOrFail());

        $this->router->bind('user', function (string $value, Route $route) {
            $server = $route->parameter('server');
            throw_unless($server instanceof Server, ModelNotFoundException::class);

            $match = $server
                ->subusers()
                ->whereRelation('user', 'uuid', '=', $value)
                ->firstOrFail();

            return $match->user;
        });

        $response = parent::handle($request, $next);
        throw_unless($response instanceof Response, UnexpectedValueException::class, 'Route binding middleware must return an HTTP response.');

        return $response;
    }
}
