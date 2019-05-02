<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\ListFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CreateFolderRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DownloadFileRequest;

class FileController extends ClientApiController
{
    /**
     * @var \Illuminate\Contracts\Cache\Factory
     */
    private $cache;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface
     */
    private $fileRepository;

    /**
     * FileController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface $fileRepository
     * @param \Illuminate\Contracts\Cache\Repository                           $cache
     */
    public function __construct(FileRepositoryInterface $fileRepository, CacheRepository $cache)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->fileRepository = $fileRepository;
    }

    /**
     * Returns a listing of files in a given directory.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\ListFilesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listDirectory(ListFilesRequest $request): JsonResponse
    {
        return JsonResponse::create([
            'contents' => $this->fileRepository->setServer($request->getModel(Server::class))->getDirectory(
                $request->get('directory') ?? '/'
            ),
        ]);
    }

    /**
     * Creates a new folder on the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\CreateFolderRequest $request
     * @return \Illuminate\Http\Response
     */
    public function createFolder(CreateFolderRequest $request): Response
    {
        $this->fileRepository
            ->setServer($request->getModel(Server::class))
            ->createDirectory($request->input('name'), $request->input('directory', '/'));

        return Response::create('s');
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
