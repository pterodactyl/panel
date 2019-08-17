<?php

namespace Pterodactyl\Repositories\Wings;

use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class WingsServerRepository extends BaseWingsRepository
{
    /**
     * Returns details about a server from the Daemon instance.
     *
     * @return array
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function getDetails(): array
    {
        try {
            $response = $this->getHttpClient()->get(
                sprintf('/api/servers/%s', $this->getServer()->uuid)
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }

        return json_decode($response->getBody()->__toString(), true);
    }
}
