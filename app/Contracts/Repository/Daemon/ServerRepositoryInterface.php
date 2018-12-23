<?php

namespace Pterodactyl\Contracts\Repository\Daemon;

use Psr\Http\Message\ResponseInterface;

interface ServerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Create a new server on the daemon for the panel.
     *
     * @param array $structure
     * @param array $overrides
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function create(array $structure, array $overrides = []): ResponseInterface;

    /**
     * Update server details on the daemon.
     *
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(array $data): ResponseInterface;

    /**
     * Mark a server to be reinstalled on the system.
     *
     * @param array|null $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function reinstall(array $data = null): ResponseInterface;

    /**
     * Mark a server as needing a container rebuild the next time the server is booted.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function rebuild(): ResponseInterface;

    /**
     * Suspend a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function suspend(): ResponseInterface;

    /**
     * Un-suspend a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function unsuspend(): ResponseInterface;

    /**
     * Delete a server on the daemon.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete(): ResponseInterface;

    /**
     * Return details on a specific server.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function details(): ResponseInterface;

    /**
     * Revoke an access key on the daemon before the time is expired.
     *
     * @param string|array $key
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function revokeAccessKey($key): ResponseInterface;
}
