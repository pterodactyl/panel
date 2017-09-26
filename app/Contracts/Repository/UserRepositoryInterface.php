<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Contracts\Repository\Attributes\SearchableInterface;

interface UserRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return all users with counts of servers and subusers of servers.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllUsersWithCounts();

    /**
     * Return all matching models for a user in a format that can be used for dropdowns.
     *
     * @param string $query
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function filterUsersByQuery($query);
}
