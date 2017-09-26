<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository\Daemon;

interface ServerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Create a new server on the daemon for the panel.
     *
     * @param int   $id
     * @param array $overrides
     * @param bool  $start
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function create($id, array $overrides = [], $start = false);

    /**
     * Set an access token and associated permissions for a server.
     *
     * @param string $key
     * @param array  $permissions
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function setSubuserKey($key, array $permissions);

    /**
     * Update server details on the daemon.
     *
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(array $data);

    /**
     * Mark a server to be reinstalled on the system.
     *
     * @param array|null $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function reinstall($data = null);

    /**
     * Mark a server as needing a container rebuild the next time the server is booted.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function rebuild();

    /**
     * Suspend a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function suspend();

    /**
     * Un-suspend a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function unsuspend();

    /**
     * Delete a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete();

    /**
     * Return detials on a specific server.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function details();
}
