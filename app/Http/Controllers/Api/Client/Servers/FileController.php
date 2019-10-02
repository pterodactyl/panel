<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Transformers\Daemon\FileObjectTransformer;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CopyFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\ListFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DeleteFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\RenameFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CreateFolderRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DownloadFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\GetFileContentsRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\WriteFileContentRequest;

class FileController extends ClientApiController
{
    /**
     * @var \Illuminate\Contracts\Cache\Factory
     */
    private $cache;

    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonFileRepository
     */
    private $fileRepository;

    /**
     * FileController constructor.
     *
     * @param \Pterodactyl\Repositories\Wings\DaemonFileRepository $fileRepository
     * @param \Illuminate\Contracts\Cache\Repository $cache
     */
    public function __construct(DaemonFileRepository $fileRepository, CacheRepository $cache)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->fileRepository = $fileRepository;
    }

    /**
     * Returns a listing of files in a given directory.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\ListFilesRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function listDirectory(ListFilesRequest $request, Server $server): array
    {
        try {
            $contents = $this->fileRepository
                ->setServer($server)
                ->getDirectory($request->get('directory') ?? '/');
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception, true);
        }

        return $this->fractal->collection($contents)
            ->transformWith($this->getTransformer(FileObjectTransformer::class))
            ->toArray();
    }

    /**
     * Return the contents of a specified file for the user.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\GetFileContentsRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\Response
     * @throws \Pterodactyl\Exceptions\Http\Server\FileSizeTooLargeException
     */
    public function getFileContents(GetFileContentsRequest $request, Server $server): Response
    {
        return Response::create(
            $this->fileRepository->setServer($server)->getContent(
                $request->get('file'), config('pterodactyl.files.max_edit_size')
            ),
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * Writes the contents of the specified file to the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\WriteFileContentRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\Response
     */
    public function writeFileContents(WriteFileContentRequest $request, Server $server): Response
    {
        $this->fileRepository->setServer($server)->putContent(
            $request->get('file'),
            $request->getContent()
        );

        return Response::create('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Creates a new folder on the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\CreateFolderRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\Response
     */
    public function createFolder(CreateFolderRequest $request, Server $server): Response
    {
        $this->fileRepository
            ->setServer($server)
            ->createDirectory($request->input('name'), $request->input('directory', '/'));

        return Response::create('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Renames a file on the remote machine.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\RenameFileRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\Response
     */
    public function renameFile(RenameFileRequest $request, Server $server): Response
    {
        $this->fileRepository
            ->setServer($server)
            ->renameFile($request->input('rename_from'), $request->input('rename_to'));

        return Response::create('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Copies a file on the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\CopyFileRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\Response
     */
    public function copyFile(CopyFileRequest $request, Server $server): Response
    {
        $this->fileRepository
            ->setServer($server)
            ->copyFile($request->input('location'));

        return Response::create('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Deletes a file or folder from the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\DeleteFileRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\Response
     */
    public function delete(DeleteFileRequest $request, Server $server): Response
    {
        $this->fileRepository
            ->setServer($server)
            ->deleteFile($request->input('location'));

        return Response::create('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Configure a reference to a file to download in the cache so that when the
     * user hits the Daemon and it verifies with the Panel they'll actually be able
     * to download that file.
     *
     * Returns the token that needs to be used when downloading the file.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\DownloadFileRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function download(DownloadFileRequest $request, Server $server): JsonResponse
    {
        $token = Uuid::uuid4()->toString();

        $this->cache->put(
            'Server:Downloads:' . $token, ['server' => $server->uuid, 'path' => $request->route()->parameter('file')], Carbon::now()->addMinutes(5)
        );

        return JsonResponse::create(['token' => $token]);
    }
}
