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

interface PackRepositoryInterface extends RepositoryInterface, SearchableInterface
{
    /**
     * Return a paginated listing of packs with their associated egg and server count.
     *
     * @param int $paginate
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateWithEggAndServerCount($paginate = 50);

    /**
     * Return a pack with the associated server models attached to it.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getWithServers($id);

    /**
     * Return all of the file archives for a given pack.
     *
     * @param int  $id
     * @param bool $collection
     * @return object|\Illuminate\Support\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getFileArchives($id, $collection = false);
}
