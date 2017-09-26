<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Daemon;

use Webmozart\Assert\Assert;
use Pterodactyl\Contracts\Repository\Daemon\PowerRepositoryInterface;
use Pterodactyl\Exceptions\Repository\Daemon\InvalidPowerSignalException;

class PowerRepository extends BaseRepository implements PowerRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function sendSignal($signal)
    {
        Assert::stringNotEmpty($signal, 'The first argument passed to sendSignal must be a non-empty string, received %s.');

        switch ($signal) {
            case self::SIGNAL_START:
            case self::SIGNAL_STOP:
            case self::SIGNAL_RESTART:
            case self::SIGNAL_KILL:
                return $this->getHttpClient()->request('PUT', '/server/power', [
                    'json' => [
                        'action' => $signal,
                    ],
                ]);
                break;
            default:
                throw new InvalidPowerSignalException('The signal ' . $signal . ' is not defined and could not be processed.');
        }
    }
}
