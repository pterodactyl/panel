<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Wings;

use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class ConfigurationRepository extends BaseRepository implements ConfigurationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(array $overrides = [])
    {
        throw new PterodactylException('This has not yet been configured.');
    }
}
