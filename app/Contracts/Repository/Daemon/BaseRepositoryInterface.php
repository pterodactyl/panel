<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository\Daemon;

interface BaseRepositoryInterface
{
    /**
     * Set the node model to be used for this daemon connection.
     *
     * @param int $id
     * @return $this
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function setNode($id);

    /**
     * Return the node model being used.
     *
     * @return \Pterodactyl\Models\Node
     */
    public function getNode();

    /**
     * Set the UUID for the server to be used in the X-Access-Server header for daemon requests.
     *
     * @param null|string $server
     * @return $this
     */
    public function setAccessServer($server = null);

    /**
     * Return the UUID of the server being used in requests.
     *
     * @return string
     */
    public function getAccessServer();

    /**
     * Set the token to be used in the X-Access-Token header for requests to the daemon.
     *
     * @param null|string $token
     * @return $this
     */
    public function setAccessToken($token = null);

    /**
     * Return the access token being used for requests.
     *
     * @return string
     */
    public function getAccessToken();

    /**
     * Return an instance of the Guzzle HTTP Client to be used for requests.
     *
     * @param array $headers
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient(array $headers = []);
}
