<?php

namespace Pterodactyl\Models\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ServerOwnerFilter implements Filter
{
    /**
     * A multi-column filter for the users table that allows an administrative user to search
     * across username & email.
     *
     * @param string $value
     */
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($query->getQuery()->from !== 'users') {
            throw new \BadMethodCallException('Cannot use the ServerOwnerFilter against a non-user model.');
        }

        $query->select('users.*')
            ->where('username', 'LIKE', '%' . $value . '%')
            ->orWhere('email', 'LIKE', '%' . $value . '%');
    }
}
