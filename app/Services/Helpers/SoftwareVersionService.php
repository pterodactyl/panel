<?php

namespace Pterodactyl\Services\Helpers;

use Exception;
use GuzzleHttp\Client;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Exceptions\Service\Helper\CdnVersionFetchingException;

class SoftwareVersionService
{
    public const VERSION_CACHE_KEY = 'pterodactyl:versioning_data';

    /**
     * @var array
     */
    private static $result;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * SoftwareVersionService constructor.
     */
    public function __construct(
        CacheRepository $cache,
        Client $client
    ) {
        $this->cache = $cache;
        $this->client = $client;

        self::$result = $this->cacheVersionData();
    }

    /**
     * Get the latest version of the panel from the CDN servers.
     *
     * @return string
     */
    public function getPanel()
    {
        return Arr::get(self::$result, 'panel') ?? 'error';
    }

    /**
     * Get the latest version of the daemon from the CDN servers.
     *
     * @return string
     */
    public function getDaemon()
    {
        return Arr::get(self::$result, 'wings') ?? 'error';
    }

    /**
     * Get the URL to the discord server.
     *
     * @return string
     */
    public function getDiscord()
    {
        return Arr::get(self::$result, 'discord') ?? 'https://pterodactyl.io/discord';
    }

    /**
     * Get the URL for donations.
     *
     * @return string
     */
    public function getDonations()
    {
        return Arr::get(self::$result, 'donations') ?? 'https://paypal.me/PterodactylSoftware';
    }

    /**
     * Determine if the current version of the panel is the latest.
     *
     * @return bool
     */
    public function isLatestPanel()
    {
        if (config()->get('app.version') === 'canary') {
            return true;
        }

        return version_compare(config()->get('app.version'), $this->getPanel()) >= 0;
    }

    /**
     * Determine if a passed daemon version string is the latest.
     *
     * @param string $version
     *
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
     *
     * @return array
     */
    protected function cacheVersionData()
    {
        return $this->cache->remember(self::VERSION_CACHE_KEY, CarbonImmutable::now()->addMinutes(config()->get('pterodactyl.cdn.cache_time', 60)), function () {
            try {
                $response = $this->client->request('GET', config()->get('pterodactyl.cdn.url'));

                if ($response->getStatusCode() === 200) {
                    return json_decode($response->getBody(), true);
                }

                throw new CdnVersionFetchingException();
            } catch (Exception $exception) {
                return [];
            }
        });
    }
}
