<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Filters;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\Server;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Server>
 */
class AdminServerFilter implements Filter
{
    /**
     * A multi-column filter for the servers table that allows an administrative user to search
     * across UUID, name, owner username, and owner email.
     *
     * @param  Builder<Server>  $query
     * @param  string  $value
     */
    public function __invoke(Builder $query, $value, string $property): void
    {
        throw_if($query->getQuery()->from !== 'servers', BadMethodCallException::class, 'Cannot use the AdminServerFilter against a non-server model.');

        $query
            ->select('servers.*')
            ->leftJoin('users', 'users.id', '=', 'servers.owner_id')
            ->where(function (Builder $builder) use ($value): void {
                $builder->where('servers.uuid', $value)
                    ->orWhere('servers.uuid', 'LIKE', "$value%")
                    ->orWhere('servers.uuidShort', $value)
                    ->orWhere('servers.external_id', $value)
                    ->orWhereRaw('LOWER(users.username) LIKE ?', ["%$value%"])
                    ->orWhereRaw('LOWER(users.email) LIKE ?', ["$value%"])
                    ->orWhereRaw('LOWER(servers.name) LIKE ?', ["%$value%"]);
            })
            ->groupBy('servers.id');
    }
}
