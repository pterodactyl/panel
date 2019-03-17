<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DownloadFileRequest;

class FileController extends ClientApiController
{
    /**
     * @var \Illuminate\Contracts\Cache\Factory
     */
    private $cache;

    /**
     * FileController constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(Repository $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Configure a reference to a file to download in the cache so that when the
     * user hits the Daemon and it verifies with the Panel they'll actually be able
     * to download that file.
     *
     * Returns the token that needs to be used when downloading the file.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\DownloadFileRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function download(DownloadFileRequest $request): JsonResponse
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = $request->getModel(Server::class);
        $token = Uuid::uuid4()->toString();

        $this->cache->put(
            'Server:Downloads:' . $token, ['server' => $server->uuid, 'path' => $request->route()->parameter('file')], Carbon::now()->addMinutes(5)
        );

        return JsonResponse::create(['token' => $token]);
    }
}
