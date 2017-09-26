<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Contracts\Repository\Daemon;

interface ConfigurationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Update the configuration details for the specified node using data from the database.
     *
     * @param array $overrides
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(array $overrides = []);
}
