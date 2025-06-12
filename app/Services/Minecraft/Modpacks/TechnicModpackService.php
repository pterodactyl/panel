<?php

namespace Pterodactyl\Services\Minecraft\Modpacks;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\BadResponseException;

class TechnicModpackService extends AbstractModpackService
{
    protected Client $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new Client([
            'headers' => [
                'User-Agent' => $this->userAgent,
            ],
            'base_uri' => 'https://api.technicpack.net/',
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
                    'q' => empty($searchQuery) ? 'Technic' : $searchQuery,
                    'build' => $this->getBuild(),
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

        foreach ($response['modpacks'] as $technicModpack) {
            $modpacks[] = [
                'id' => $technicModpack['slug'],
                'name' => $technicModpack['name'],
                'description' => null,
                'url' => $technicModpack['url'],
                'icon_url' => $technicModpack['iconUrl'],
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
        // We only have the latest for Technic
        return [
            [
                'id' => 'latest',
                'name' => 'Latest',
            ],
        ];
    }

    /**
     * Get build from Technic API.
     */
    protected function getBuild(): ?string
    {
        return Cache::remember('technic-build', 3600, function () {
            try {
                $response = json_decode($this->client->get('launcher/version/stable4')->getBody(), true);
            } catch (TransferException $e) {
                if ($e instanceof BadResponseException) {
                    logger()->error('Received bad response when fetching Technic build.', ['response' => \GuzzleHttp\Psr7\Message::toString($e->getResponse())]);
                }

                return 822;
            }

            return $response['build'];
        });
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
