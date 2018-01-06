<?php

namespace Pterodactyl\Contracts\Repository\Daemon;

use GuzzleHttp\Client;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

interface BaseRepositoryInterface
{
    /**
     * Set the node model to be used for this daemon connection.
     *
     * @param \Pterodactyl\Models\Node $node
     * @return $this
     */
    public function setNode(Node $node);

    /**
     * Return the node model being used.
     *
     * @return \Pterodactyl\Models\Node|null
     */
    public function getNode();

    /**
     * Set the Server model to use when requesting information from the Daemon.
     *
     * @param \Pterodactyl\Models\Server $server
     * @return $this
     */
    public function setServer(Server $server);

    /**
     * Return the Server model.
     *
     * @return \Pterodactyl\Models\Server|null
     */
    public function getServer();

    /**
     * Set the token to be used in the X-Access-Token header for requests to the daemon.
     *
     * @param string $token
     * @return $this
     */
    public function setToken(string $token);

    /**
     * Return the access token being used for requests.
     *
     * @return string|null
     */
    public function getToken();

    /**
     * Return an instance of the Guzzle HTTP Client to be used for requests.
     *
     * @param array $headers
     * @return \GuzzleHttp\Client
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getHttpClient(array $headers = []): Client;
}
