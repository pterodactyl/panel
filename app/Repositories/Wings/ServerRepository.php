<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Wings;

use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface;

class ServerRepository extends BaseRepository implements ServerRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(array $structure, array $overrides = []): ResponseInterface
    {
        throw new PterodactylException('This feature is not yet implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $data)
    {
        throw new PterodactylException('This feature is not yet implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function reinstall($data = null)
    {
        throw new PterodactylException('This feature is not yet implemented.');
    }

    /**
     * {@inheritdoc}
     */
    public function rebuild()
    {
        return $this->getHttpClient()->request('POST', 'server/' . $this->getAccessServer() . '/rebuild');
    }

    /**
     * {@inheritdoc}
     */
    public function suspend()
    {
        return $this->getHttpClient()->request('POST', 'server/' . $this->getAccessServer() . '/suspend');
    }

    /**
     * {@inheritdoc}
     */
    public function unsuspend()
    {
        return $this->getHttpClient()->request('POST', 'server/' . $this->getAccessServer() . '/unsuspend');
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->getHttpClient()->request('DELETE', 'server/' . $this->getAccessServer());
    }

    /**
     * {@inheritdoc}
     */
    public function details()
    {
        return $this->getHttpClient()->request('GET', 'server/' . $this->getAccessServer());
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessKey($key)
    {
        throw new PterodactylException('This feature is not yet implemented.');
    }
}
