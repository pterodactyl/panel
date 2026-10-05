<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Helpers;

use Carbon\CarbonImmutable;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Exceptions\Service\Helper\CdnVersionFetchingException;
use Pterodactyl\Support\JsonValueGuard;

class SoftwareVersionService
{
    public const string VERSION_CACHE_KEY = 'pterodactyl:versioning_data';

    /** @var SoftwareVersionData|null */
    private ?array $result = null;

    /**
     * SoftwareVersionService constructor.
     */
    public function __construct(
        protected CacheRepository $cache,
        protected Client $client,
    ) {}

    /**
     * Get the latest version of the panel from the CDN servers.
     */
    public function getPanel(): string
    {
        return $this->versionData()['panel'] ?? 'error';
    }

    /**
     * Get the latest version of the daemon from the CDN servers.
     */
    public function getDaemon(): string
    {
        return $this->versionData()['wings'] ?? 'error';
    }

    /**
     * Get the URL to the discord server.
     */
    public function getDiscord(): string
    {
        return $this->versionData()['discord'] ?? 'https://pterodactyl.io/discord';
    }

    /**
     * Get the URL for donations.
     */
    public function getDonations(): string
    {
        return $this->versionData()['donations'] ?? 'https://github.com/sponsors/matthewpi';
    }

    /**
     * Determine if the current version of the panel is the latest.
     */
    public function isLatestPanel(): bool
    {
        if (config('app.version') === 'canary') {
            return true;
        }

        return version_compare(JsonValueGuard::string(config('app.version')), $this->getPanel()) >= 0;
    }

    /**
     * Determine if a passed daemon version string is the latest.
     */
    public function isLatestDaemon(string $version): bool
    {
        if ($version === 'develop') {
            return true;
        }

        return version_compare($version, $this->getDaemon()) >= 0;
    }

    /**
     * Keeps the versioning cache up-to-date with the latest results from the CDN.
     *
     * @return SoftwareVersionData
     */
    protected function versionData(): array
    {
        $this->result ??= $this->cacheVersionData();

        return $this->result;
    }

    /**
     * @return SoftwareVersionData
     */
    protected function cacheVersionData(): array
    {
        $cached = $this->cache->remember(self::VERSION_CACHE_KEY, CarbonImmutable::now()->addMinutes(JsonValueGuard::integer(config('pterodactyl.cdn.cache_time', 60))), function (): array {
            try {
                $response = $this->client->request('GET', JsonValueGuard::string(config('pterodactyl.cdn.url')), ['timeout' => 5, 'connect_timeout' => 2]);

                if ($response->getStatusCode() === 200) {
                    return JsonValueGuard::decodeArray8($response->getBody()->__toString());
                }

                throw new CdnVersionFetchingException;
            } catch (Exception) {
                return [];
            }
        });

        return $this->normalizeVersionData($cached);
    }

    /**
     * @param  JsonInputValue  $value
     * @return SoftwareVersionData
     */
    private function normalizeVersionData(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach (['panel', 'wings', 'discord', 'donations'] as $key) {
            $item = $value[$key] ?? null;
            if (is_string($item)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
    }
}
