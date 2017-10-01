<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Repositories\Daemon;

use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class ConfigurationRepository extends BaseRepository implements ConfigurationRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(array $overrides = [])
    {
        $node = $this->getNode();
        $structure = [
            'web' => [
                'listen' => $node->daemonListen,
                'ssl' => [
                    'enabled' => (! $node->behind_proxy && $node->scheme === 'https'),
                ],
            ],
            'sftp' => [
                'path' => $node->daemonBase,
                'port' => $node->daemonSFTP,
            ],
            'remote' => [
                'base' => $this->config->get('app.url'),
            ],
            'uploads' => [
                'size_limit' => $node->upload_size,
            ],
            'keys' => [
                $node->daemonSecret,
            ],
        ];

        return $this->getHttpClient()->request('PATCH', 'config', [
            'json' => array_merge($structure, $overrides),
        ]);
    }
}
