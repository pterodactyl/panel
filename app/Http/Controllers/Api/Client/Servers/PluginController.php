<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\PullFileRequest;

class PluginController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonFileRepository
     */
    private $fileRepository;

    /**
     * PluginController constructor.
     */
    public function __construct(DaemonFileRepository $fileRepository)
    {
        parent::__construct();

        $this->fileRepository = $fileRepository;
    }

    /**
     * List all plugins from the Spigot API.
     * 
     * @throws DisplayException
     */
    public function index(Request $request): ?array
    {
        $query = $request->input('query');
        if (!$query) return null;

        $client = new Client();

        $api = 'https://api.spiget.org/v2/search/resources/' . urlencode($query) .'?page=1&size=18';

        try {
            $res = $client->request('GET', $api, ['headers' => ['User-Agent' => 'jexactyl/3.x']] );
        } catch (DisplayException $e) {
            throw new DisplayException('Couldn\'t find any results for that query.');
        };

        $plugins = json_decode($res->getBody(), true);

        return [
            'success' => true,
            'data' => [
                'plugins' => $plugins,
            ],
        ];
    }

    /**
     * Install the plugin using the Panel.
     * 
     * @throws DisplayException
     */
    public function install(PullFileRequest $request, Server $server, int $id): JsonResponse
    {
        $this->fileRepository->setServer($server)->pull(
            'https://cdn.spiget.org/file/spiget-resources/' . $id . '.jar',
            '/plugins',
            $request->safe(['filename', 'use_header', 'foreground'])
        );

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}