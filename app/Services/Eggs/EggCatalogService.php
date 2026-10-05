<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Eggs;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Data\EggCatalogEntry;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EggCatalogService
{
    public const string INDEX_URL = 'https://eggs.pterodactyl.io/api/eggs.json';

    private const string CACHE_KEY = 'egg_catalog:pterodactyl:v1';

    /** @return list<EggCatalogEntry> */
    public function all(): array
    {
        $contents = Cache::remember(self::CACHE_KEY, 3600, function (): string {
            $contents = $this->downloadUrl(self::INDEX_URL);
            EggCatalogEntry::listFromJson($contents);

            return $contents;
        });

        return EggCatalogEntry::listFromJson($contents);
    }

    public function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function download(EggCatalogEntry $entry): string
    {
        return $this->downloadUrl($entry->downloadUrl());
    }

    private function downloadUrl(string $url): string
    {
        try {
            $response = Http::acceptJson()->connectTimeout(3)->timeout(10)->withoutRedirecting()->get($url);
        } catch (ConnectionException $connectionException) {
            throw new HttpException(502, 'Could not reach the Pterodactyl egg catalog. Please try again.', $connectionException);
        }

        throw_unless($response->successful(), HttpException::class, 502, 'Could not download from the Pterodactyl egg catalog. Please try again.');

        return $response->body();
    }
}
