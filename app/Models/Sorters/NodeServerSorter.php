<?php

namespace Pterodactyl\Models\Sorters;


use BadMethodCallException;
use Spatie\QueryBuilder\Sorts\Sort;
use Illuminate\Database\Eloquent\Builder;

class NodeServerSorter implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        if ($query->getQuery()->from !== 'servers') {
            throw new BadMethodCallException('Cannot use the NodeServerSorter against a non-server model.');
        }

        $query->join('nodes', 'nodes.id', '=', 'servers.node_id')->orderBy('nodes.name', $descending ? 'DESC' : 'ASC');
    }
}
