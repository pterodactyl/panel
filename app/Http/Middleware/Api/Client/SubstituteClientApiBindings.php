<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Illuminate\Container\Container;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Http\Middleware\Api\ApiSubstituteBindings;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class SubstituteClientApiBindings extends ApiSubstituteBindings
{
    /**
     * Perform substitution of route parameters without triggering
     * a 404 error if a model is not found.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Override default behavior of the model binding to use a specific table
        // column rather than the default 'id'.
        $this->router->bind('server', function ($value) use ($request) {
            try {
                $column = 'uuidShort';
                if (preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $value)) {
                    $column = 'uuid';
                }

                return Container::getInstance()->make(ServerRepositoryInterface::class)->findFirstWhere([
                    [$column, '=', $value],
                ]);
            } catch (RecordNotFoundException $ex) {
                $request->attributes->set('is_missing_model', true);

                return null;
            }
        });

        $this->router->bind('database', function ($value) use ($request) {
            try {
                $id = Container::getInstance()->make(HashidsInterface::class)->decodeFirst($value);

                return Container::getInstance()->make(DatabaseRepositoryInterface::class)->findFirstWhere([
                    ['id', '=', $id],
                ]);
            } catch (RecordNotFoundException $exception) {
                $request->attributes->set('is_missing_model', true);

                return null;
            }
        });

        return parent::handle($request, $next);
    }
}
