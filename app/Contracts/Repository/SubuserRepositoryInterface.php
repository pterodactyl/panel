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
     * @return \Pterodactyl\Models\Subuser
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
     * Return a subuser and associated permissions given a user_id and server_id.
     *
     * @param int $user
     * @param int $server
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithPermissionsUsingUserAndServer($user, $server);

    /**
     * Find a subuser and return with server and permissions relationships.
     *
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithServerAndPermissions($id);

    /**
     * Return a subuser and their associated connection key for a server.
     *
     * @param int $user
     * @param int $server
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithKey($user, $server);
}
