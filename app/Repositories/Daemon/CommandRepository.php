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
use Pterodactyl\Contracts\Repository\Daemon\CommandRepositoryInterface;

class CommandRepository extends BaseRepository implements CommandRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function send($command)
    {
        Assert::stringNotEmpty($command, 'First argument passed to send must be a non-empty string, received %s.');

        return $this->getHttpClient()->request('POST', '/server/command', [
            'json' => [
                'command' => $command,
            ],
        ]);
    }
}
