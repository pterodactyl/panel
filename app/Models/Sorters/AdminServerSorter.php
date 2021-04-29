<?php

namespace Pterodactyl\Models\Sorters;


use BadMethodCallException;
use Spatie\QueryBuilder\Sorts\Sort;
use Illuminate\Database\Eloquent\Builder;

class AdminServerSorter implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        if ($query->getQuery()->from !== 'servers') {
            throw new BadMethodCallException('Cannot use the AdminServerSorter against a non-server model.');
        }

        $query->join('users as us', 'us.id', '=', 'servers.owner_id')->orderBy('us.username', $descending ? 'DESC' : 'ASC');
    }
}
