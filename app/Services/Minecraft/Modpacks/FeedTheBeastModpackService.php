<?php

namespace Pterodactyl\Services\Minecraft\Modpacks;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\BadResponseException;

class FeedTheBeastModpackService extends AbstractModpackService
{
    protected Client $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new Client([
            'base_uri' => 'https://api.feed-the-beast.com/v1/modpacks/public/modpack/',
            'headers' => [
                'User-Agent' => $this->userAgent,
            ],
        ]);
    }

    /**
     * Search for modpacks on the provider.
     */
    public function search(string $searchQuery, int $pageSize, int $page): array
    {
        $uri = (empty($searchQuery) ? 'popular/installs/' : 'search/') . $pageSize;

        try {
            $response = json_decode($this->client->get($uri, [
                'query' => [
                    'term' => $searchQuery,
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

        if (!isset($response['packs'])) {
            return [
                'data' => [],
                'total' => 0,
            ];
        }

        $requests = [];

        foreach ($response['packs'] as $feedthebeastPackId) {
            if ($feedthebeastPackId == 81) { // "Vanilla" modpack.
                continue;
            }

            $requests[] = new Request('GET', (string) $feedthebeastPackId);
        }

        $modpacks = [];

        $pool = new Pool($this->client, $requests, [
            'concurrency' => 5,
            'fulfilled' => function (Response $response, $index) use (&$modpacks) {
                if ($response->getStatusCode() != 200) {
                    logger()->error('Received bad response when fetching modpacks.', ['response' => \GuzzleHttp\Psr7\Message::toString($response)]);

                    return;
                }

                $feedthebeastModpack = json_decode($response->getBody(), true);

                if ($feedthebeastModpack['status'] === 'error') {
                    logger()->error('Received bad response when fetching modpacks.', ['response' => \GuzzleHttp\Psr7\Message::toString($response)]);

                    return;
                }

                // There's at least always one `square` art.
                $iconUrl = array_values(array_filter($feedthebeastModpack['art'], function ($art) {
                    return $art['type'] === 'square';
                }))[0]['url'];

                $modpacks[$index] = [
                    'id' => (string) $feedthebeastModpack['id'],
                    'name' => $feedthebeastModpack['name'],
                    'description' => $feedthebeastModpack['description'],
                    'url' => 'https://feed-the-beast.com/modpacks/' . $feedthebeastModpack['id'],
                    'icon_url' => $iconUrl,
                ];
            },
        ]);

        $pool->promise()->wait();

        ksort($modpacks);
        $modpacks = array_values($modpacks);

        return [
            'data' => $modpacks,
            'total' => count($modpacks),
        ];
    }

    /**
     * Get the versions of a specific modpack for the provider.
     */
    public function versions(string $modpackId): array
    {
        try {
            $response = json_decode($this->client->get($modpackId)->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching modpack versions.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [];
        }

        $versions = [];

        foreach ($response['versions'] as $feedthebeastModpackVersion) {
            $versions[] = [
                'id' => (string) $feedthebeastModpackVersion['id'],
                'name' => $feedthebeastModpackVersion['name'],
            ];
        }

        // Latest first
        $versions = array_reverse($versions);

        return $versions;
    }

    /**
     * Get modpack details.
     */
    public function details(string $modpackId): array
    {
        try {
            $response = json_decode($this->client->get($modpackId)->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching modpack versions.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [];
        }

        // There's always at least one `square` art.
        $iconUrl = array_values(array_filter($response['art'], function ($art) {
            return $art['type'] === 'square';
        }))[0]['url'];

        return [
            'name' => $response['name'],
            'description' => $response['description'],
            'url' => 'https://feed-the-beast.com/modpacks/' . $response['id'],
            'icon_url' => $iconUrl,
        ];
    }
}
