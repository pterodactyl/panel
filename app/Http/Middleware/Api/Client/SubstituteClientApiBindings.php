<?php

namespace Pterodactyl\Http\Middleware\Api\Client;

use Closure;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Database;
use Illuminate\Container\Container;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Pterodactyl\Http\Middleware\Api\ApiSubstituteBindings;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class SubstituteClientApiBindings extends ApiSubstituteBindings
{
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

        $this->router->bind('database', function ($value) {
            $id = Container::getInstance()->make(HashidsInterface::class)->decodeFirst($value);

            return Database::query()->where('id', $id)->firstOrFail();
        });

        $this->router->bind('backup', function ($value) {
            return Backup::query()->where('uuid', $value)->firstOrFail();
        });

        $this->router->bind('user', function ($value) {
            return User::query()->where('uuid', $value)->firstOrFail();
        });

        return parent::handle($request, $next);
    }
}
