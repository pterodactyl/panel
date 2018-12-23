<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Helpers;

use stdClass;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Exceptions\Service\Helper\CdnVersionFetchingException;

class SoftwareVersionService
{
    const VERSION_CACHE_KEY = 'pterodactyl:versions';

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * SoftwareVersionService constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository  $cache
     * @param \GuzzleHttp\Client                      $client
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(
        CacheRepository $cache,
        Client $client,
        ConfigRepository $config
    ) {
        $this->cache = $cache;
        $this->client = $client;
        $this->config = $config;

        $this->cacheVersionData();
    }

    /**
     * Get the latest version of the panel from the CDN servers.
     *
     * @return string
     */
    public function getPanel()
    {
        return object_get($this->cache->get(self::VERSION_CACHE_KEY), 'panel', 'error');
    }

    /**
     * Get the latest version of the daemon from the CDN servers.
     *
     * @return string
     */
    public function getDaemon()
    {
        return object_get($this->cache->get(self::VERSION_CACHE_KEY), 'daemon', 'error');
    }

    /**
     * Get the URL to the discord server.
     *
     * @return string
     */
    public function getDiscord()
    {
        return object_get($this->cache->get(self::VERSION_CACHE_KEY), 'discord', 'https://pterodactyl.io/discord');
    }

    /**
     * Determine if the current version of the panel is the latest.
     *
     * @return bool
     */
    public function isLatestPanel()
    {
        if ($this->config->get('app.version') === 'canary') {
            return true;
        }

        return version_compare($this->config->get('app.version'), $this->getPanel()) >= 0;
    }

    /**
     * Determine if a passed daemon version string is the latest.
     *
     * @param string $version
     * @return bool
     */
    public function isLatestDaemon($version)
    {
        if ($version === '0.0.0-canary') {
            return true;
        }

        return version_compare($version, $this->getDaemon()) >= 0;
    }

    /**
     * Keeps the versioning cache up-to-date with the latest results from the CDN.
     */
    protected function cacheVersionData()
    {
        $this->cache->remember(self::VERSION_CACHE_KEY, $this->config->get('pterodactyl.cdn.cache_time'), function () {
            try {
                $response = $this->client->request('GET', $this->config->get('pterodactyl.cdn.url'));

                if ($response->getStatusCode() === 200) {
                    return json_decode($response->getBody());
                }

                throw new CdnVersionFetchingException;
            } catch (Exception $exception) {
                return new stdClass();
            }
        });
    }
}
