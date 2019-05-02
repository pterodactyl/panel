<?php

namespace Pterodactyl\Repositories\Wings;

use GuzzleHttp\Client;
use Pterodactyl\Repositories\Daemon\BaseRepository;
use Pterodactyl\Contracts\Repository\Daemon\BaseRepositoryInterface;

abstract class BaseWingsRepository extends BaseRepository implements BaseRepositoryInterface
{
    /**
     * Return an instance of the Guzzle HTTP Client to be used for requests.
     *
     * @param array $headers
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient(array $headers = []): Client
    {
        // We're just going to extend the parent client here since that logic is already quite
        // sound and does everything we need it to aside from provide the correct base URL
        // and authentication headers.
        $client = parent::getHttpClient($headers);

        return new Client(array_merge($client->getConfig(), [
            'base_uri' => $this->getNode()->getConnectionAddress(),
            'headers' => [
                'Authorization' => 'Bearer ' . ($this->getToken() ?? $this->getNode()->daemonSecret),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]));
    }
}
