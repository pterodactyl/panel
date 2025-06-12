<?php

namespace Pterodactyl\Services\Minecraft\Modpacks;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\BadResponseException;

enum CurseForgeSortField: int
{
    case Featured = 1;
    case Popularity = 2;
    case LastUpdated = 3;
    case Name = 4;
    case Author = 5;
    case TotalDownloads = 6;
    case Category = 7;
    case GameVersion = 8;
    case EarlyAccess = 9;
    case FeaturedReleased = 10;
    case ReleasedDate = 11;
    case Rating = 12;
};

class CurseForgeModpackService extends AbstractModpackService
{
    public const CURSEFORGE_MINECRAFT_GAME_ID = 432;
    public const CURSEFORGE_MINECRAFT_MODPACKS_CLASS_ID = 4471;

    protected Client $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new Client([
            'headers' => [
                'User-Agent' => $this->userAgent,
                'X-API-Key' => config('services.curseforge_api_key'),
            ],
            'base_uri' => 'https://api.curseforge.com/v1/',
        ]);
    }

    /**
     * Search for modpacks on the provider.
     */
    public function search(string $searchQuery, int $pageSize, int $page): array
    {
        try {
            $response = json_decode($this->client->get('mods/search', [
                'query' => [
                    'index' => ($page - 1) * $pageSize,
                    'pageSize' => $pageSize,
                    'gameId' => self::CURSEFORGE_MINECRAFT_GAME_ID,
                    'classId' => self::CURSEFORGE_MINECRAFT_MODPACKS_CLASS_ID,
                    'searchFilter' => $searchQuery,
                    'sortField' => CurseForgeSortField::Popularity->value,
                    'sortOrder' => 'desc',
                ],
            ])->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching modpacks.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [
                'data' => [],
                'total' => 0,
            ];
        }

        $modpacks = [];

        foreach ($response['data'] as $curseforgeModpack) {
            $modpacks[] = [
                'id' => (string) $curseforgeModpack['id'],
                'name' => $curseforgeModpack['name'],
                'description' => $curseforgeModpack['summary'],
                'url' => $curseforgeModpack['links']['websiteUrl'],
                'icon_url' => $curseforgeModpack['logo']['thumbnailUrl'],
            ];
        }

        // https://docs.curseforge.com/#search-mods
        // index + pageSize <= 10000
        $maximumPage = (10000 - $pageSize) / $pageSize + 1;

        return [
            'data' => $modpacks,
            'total' => min($maximumPage * $pageSize, $response['pagination']['totalCount']),
        ];
    }

    /**
     * Get the versions of a specific modpack for the provider.
     */
    public function versions(string $modpackId): array
    {
        try {
            $response = json_decode($this->client->get('mods/' . $modpackId . '/files')->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching modpack files.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [];
        }

        $versions = [];

        foreach ($response['data'] as $curseforgeModpackFile) {
            $versions[] = [
                'id' => (string) $curseforgeModpackFile['id'],
                'name' => $curseforgeModpackFile['displayName'],
            ];
        }

        return $versions;
    }

    /**
     * Get modpack details.
     */
    public function details(string $modpackId): array
    {
        try {
            $response = json_decode($this->client->get('mods/' . $modpackId)->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching modpack details.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [];
        }

        return [
            'name' => $response['data']['name'],
            'icon_url' => $response['data']['logo']['thumbnailUrl'],
            'url' => $response['data']['links']['websiteUrl'],
            'description' => $response['data']['summary'],
        ];
    }
}
