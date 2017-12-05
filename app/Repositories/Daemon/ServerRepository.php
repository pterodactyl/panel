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
use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface;

class ServerRepository extends BaseRepository implements ServerRepositoryInterface
{
    /**
     * Create a new server on the daemon for the panel.
     *
     * @param array $structure
     * @param array $overrides
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\RequestException
     */
    public function create(array $structure, array $overrides = []): ResponseInterface
    {
        // Loop through overrides.
        foreach ($overrides as $key => $value) {
            array_set($structure, $key, $value);
        }

        return $this->getHttpClient()->request('POST', 'servers', [
            'json' => $structure,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $data)
    {
        return $this->getHttpClient()->request('PATCH', 'server', [
            'json' => $data,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function reinstall($data = null)
    {
        Assert::nullOrIsArray($data, 'First argument passed to reinstall must be null or an array, received %s.');

        if (is_null($data)) {
            return $this->getHttpClient()->request('POST', 'server/reinstall');
        }

        return $this->getHttpClient()->request('POST', 'server/reinstall', [
            'json' => $data,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function rebuild()
    {
        return $this->getHttpClient()->request('POST', 'server/rebuild');
    }

    /**
     * {@inheritdoc}
     */
    public function suspend()
    {
        return $this->getHttpClient()->request('POST', 'server/suspend');
    }

    /**
     * {@inheritdoc}
     */
    public function unsuspend()
    {
        return $this->getHttpClient()->request('POST', 'server/unsuspend');
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->getHttpClient()->request('DELETE', 'servers');
    }

    /**
     * {@inheritdoc}
     */
    public function details()
    {
        return $this->getHttpClient()->request('GET', 'server');
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessKey($key)
    {
        if (is_array($key)) {
            return $this->getHttpClient()->request('POST', 'keys', [
                'json' => $key,
            ]);
        }

        Assert::stringNotEmpty($key, 'First argument passed to revokeAccessKey must be a non-empty string or array, received %s.');

        return $this->getHttpClient()->request('DELETE', 'keys/' . $key);
    }
}
