<?php

namespace Pterodactyl\Repositories\Daemon;

use Psr\Http\Message\ResponseInterface;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class ConfigurationRepository extends BaseRepository implements ConfigurationRepositoryInterface
{
    /**
     * Update the configuration details for the specified node using data from the database.
     *
     * @param array $overrides
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function update(array $overrides = []): ResponseInterface
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
                'base' => config('app.url'),
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
