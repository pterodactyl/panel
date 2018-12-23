<?php

namespace Pterodactyl\Repositories\Daemon;

use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface;
use Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException;

class PowerRepository extends BaseRepository implements PowerRepositoryInterface
{
    /**
     * Send a power signal to a server.
     *
     * @param string $signal
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidPowerSignalException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSignal(string $signal): ResponseInterface
    {
        switch ($signal) {
            case self::SIGNAL_START:
            case self::SIGNAL_STOP:
            case self::SIGNAL_RESTART:
            case self::SIGNAL_KILL:
                return $this->getHttpClient()->request('PUT', 'server/power', [
                    'json' => [
                        'action' => $signal,
                    ],
                ]);
            default:
                throw new InvalidPowerSignalException('The signal "' . $signal . '" is not defined and could not be processed.');
        }
    }
}
