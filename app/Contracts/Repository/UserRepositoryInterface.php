<?php

namespace Pterodactyl\Contracts\Repository;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Contracts\Repository\Attributes\SearchableInterface;

interface UserRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return all users with counts of servers and subusers of servers.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllUsersWithCounts(): LengthAwarePaginator;

    /**
     * Return all matching models for a user in a format that can be used for dropdowns.
     *
     * @param string|null $query
     * @return \Illuminate\Support\Collection
     */
    public function filterUsersByQuery(?string $query): Collection;
}
