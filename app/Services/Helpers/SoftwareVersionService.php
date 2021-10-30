<?php

namespace Pterodactyl\Services\Helpers;

use Exception;
use GuzzleHttp\Client;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
     * Gets the current version of the panel that is being used.
     */
    public function getVersion(): string
    {
        return config()->get('app.version');
    }

    /**
     * Get the latest version of the panel from the CDN servers.
     */
    public function getLatestPanel(): string
    {
        return Arr::get(self::$result, 'panel') ?? 'error';
    }

    /**
     * Get the latest version of wings from the CDN servers.
     */
    public function getLatestWings(): string
    {
        return Arr::get(self::$result, 'wings') ?? 'error';
    }

    /**
     * Determine if the current version of the panel is the latest.
     *
     * @return bool
     */
    public function isLatestPanel()
    {
        $version = $this->getVersion();
        if ($version === 'canary') {
            return true;
        }

        return version_compare($version, $this->getLatestPanel()) >= 0;
    }

    /**
     * Determine if a passed wings version is the latest.
     *
     * @return bool
     */
    public function isLatestWings(string $version)
    {
        // If the version is 'canary' or starts with 'dev-', mark it as the latest.
        if ($version === 'canary' || Str::startsWith($version, 'dev-')) {
            return true;
        }

        return version_compare($version, $this->getLatestWings()) >= 0;
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

    /**
     * Return version information for the footer.
     */
    protected function versionData(): array
    {
        return $this->cache->remember('git-version', 5, function () {
            $configVersion = config()->get('app.version');

            if (file_exists(base_path('.git/HEAD'))) {
                $head = explode(' ', file_get_contents(base_path('.git/HEAD')));

                if (array_key_exists(1, $head)) {
                    $path = base_path('.git/' . trim($head[1]));
                }
            }

            if (isset($path) && file_exists($path)) {
                return [
                    'version' => substr(file_get_contents($path), 0, 8),
                    'is_git' => true,
                ];
            }

            return [
                'version' => $configVersion,
                'is_git' => false,
            ];
        });
    }

    /**
     * ?
     */
    public function getVersionData(): array
    {
        $versionData = $this->versionData();
        if ($versionData['is_git']) {
            $git = $versionData['version'];
        } else {
            $git = null;
        }

        return [
            'panel' => [
                'current' => $this->getVersion(),
                'latest' => $this->getLatestPanel(),
            ],

            'wings' => [
                'latest' => $this->getLatestWings(),
            ],

            'git' => $git,
        ];
    }
}
