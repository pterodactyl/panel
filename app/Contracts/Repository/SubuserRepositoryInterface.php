<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository;

interface SubuserRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a subuser with the associated server relationship.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithServer($id);

    /**
     * Return a subuser with the associated permissions relationship.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithPermissions($id);

    /**
     * Find a subuser and return with server and permissions relationships.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithServerAndPermissions($id);
}
