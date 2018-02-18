<?php

namespace Pterodactyl\Contracts\Repository\Daemon;

use Psr\Http\Message\ResponseInterface;

interface ConfigurationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Update the configuration details for the specified node using data from the database.
     *
     * @param array $overrides
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(array $overrides = []): ResponseInterface;
}
