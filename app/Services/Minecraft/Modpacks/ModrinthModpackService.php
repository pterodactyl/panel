<?php

namespace Pterodactyl\Services\Minecraft\Modpacks;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\BadResponseException;

class ModrinthModpackService extends AbstractModpackService
{
    protected Client $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new Client([
            'headers' => [
                'User-Agent' => $this->userAgent,
            ],
            'base_uri' => 'https://api.modrinth.com/v2/',
        ]);
    }

    /**
     * Search for modpacks on the provider.
     */
    public function search(string $searchQuery, int $pageSize, int $page): array
    {
        try {
            $response = json_decode($this->client->get('search', [
                'query' => [
                    'query' => $searchQuery,
                    'facets' => '[["project_type:modpack"],["server_side!=unsupported"]]',
                    'index' => 'relevance',
                    'offset' => ($page - 1) * $pageSize,
                    'limit' => $pageSize,
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

        foreach ($response['hits'] as $modrinthModpack) {
            $modpacks[] = [
                'id' => $modrinthModpack['project_id'],
                'name' => $modrinthModpack['title'],
                'description' => $modrinthModpack['description'],
                'url' => 'https://modrinth.com/modpack/' . $modrinthModpack['slug'],
                'icon_url' => empty($modrinthModpack['icon_url']) ? null : $modrinthModpack['icon_url'],
            ];
        }

        return [
            'data' => $modpacks,
            'total' => $response['total_hits'],
        ];
    }

    /**
     * Get the versions of a specific modpack for the provider.
     */
    public function versions(string $modpackId): array
    {
        try {
            $response = json_decode($this->client->get('project/' . $modpackId . '/version')->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching modpack versions.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [];
        }

        $versions = [];

        foreach ($response as $modrinthModpackVersion) {
            $versions[] = [
                'id' => $modrinthModpackVersion['id'],
                'name' => $modrinthModpackVersion['name'],
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
            $response = json_decode($this->client->get('project/' . $modpackId)->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching modpack details.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [];
        }

        return [
            'name' => $response['title'],
            'description' => $response['description'],
            'url' => 'https://modrinth.com/modpack/' . $response['slug'],
            'icon_url' => empty($response['icon_url']) ? null : $response['icon_url'],
        ];
    }
}
