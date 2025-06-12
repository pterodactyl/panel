<?php

namespace Pterodactyl\Services\Minecraft\Modpacks;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\BadResponseException;

class ATLauncherModpackService extends AbstractModpackService
{
    protected Client $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new Client([
            'base_uri' => 'https://api.atlauncher.com/v2/',
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
        $query = 'query { packs(first: ' . $pageSize . ') { id safeName name description websiteUrl } }';
        $index = 'packs';

        if (!empty($searchQuery)) {
            $query = 'query { searchPacks(first: ' . $pageSize . ', query: "' . $searchQuery . '") { id safeName name description websiteUrl } }';
            $index = 'searchPacks';
        }

        try {
            $response = json_decode($this->client->post('graphql', [
                RequestOptions::JSON => ['query' => $query],
            ])->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching ATLauncher modpacks.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [
                'data' => [],
                'total' => 0,
            ];
        }

        if (!isset($response['data'][$index])) {
            return [
                'data' => [],
                'total' => 0,
            ];
        }

        $modpacks = [];

        foreach ($response['data'][$index] as $atmodpack) {
            $modpacks[] = [
                'id' => $atmodpack['id'],
                'name' => $atmodpack['name'],
                'description' => $atmodpack['description'],
                'url' => 'https://atlauncher.com/pack/' . $atmodpack['safeName'],
                'icon_url' => 'https://cdn.atlcdn.net/images/packs/' . strtolower($atmodpack['safeName']) . '.png',
            ];
        }

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
        $query = 'query { pack(pack: { id: ' . $modpackId . ' }) { versions(first: 100) { version } } }';

        try {
            $response = json_decode($this->client->post('graphql', [
                RequestOptions::JSON => ['query' => $query],
            ])->getBody(), true);
        } catch (TransferException $e) {
            if ($e instanceof BadResponseException) {
                logger()->error('Received bad response when fetching ATLauncher modpack versions.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
            }

            return [];
        }

        $versions = [];

        foreach ($response['data']['pack']['versions'] as $atlauncherModpackVersion) {
            $versions[] = [
                'id' => $atlauncherModpackVersion['version'],
                'name' => $atlauncherModpackVersion['version'],
            ];
        }

        return $versions;
    }

    /**
     * Get modpack details.
     */
    public function details(string $modpackId): array
    {
        return [
            'name' => 'Not available yet',
            'icon_url' => null,
            'url' => null,
            'description' => 'Data not available yet',
        ];
    }
}
