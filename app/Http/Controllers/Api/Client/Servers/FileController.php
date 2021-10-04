<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Transformers\Daemon\FileObjectTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CopyFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\PullFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\ListFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\ChmodFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DeleteFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\RenameFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CreateFolderRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CompressFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DecompressFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\GetFileContentsRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\WriteFileContentRequest;

class FileController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Wings\DaemonFileRepository
     */
    private $fileRepository;

    /**
     * @var \Illuminate\Contracts\Routing\ResponseFactory
     */
    private $responseFactory;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeJWTService
     */
    private $jwtService;

    /**
     * FileController constructor.
     */
    public function __construct(
        ResponseFactory $responseFactory,
        NodeJWTService $jwtService,
        DaemonFileRepository $fileRepository
    ) {
        parent::__construct();

        $this->fileRepository = $fileRepository;
        $this->responseFactory = $responseFactory;
        $this->jwtService = $jwtService;
    }

    /**
     * Returns a listing of files in a given directory.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function directory(ListFilesRequest $request, Server $server): array
    {
        $contents = $this->fileRepository
            ->setServer($server)
            ->getDirectory($request->get('directory') ?? '/');

        return $this->fractal->collection($contents)
            ->transformWith($this->getTransformer(FileObjectTransformer::class))
            ->toArray();
    }

    /**
     * Return the contents of a specified file for the user.
     *
     * @throws \Throwable
     */
    public function contents(GetFileContentsRequest $request, Server $server): Response
    {
        $response = $this->fileRepository->setServer($server)->getContent(
            $request->get('file'),
            config('pterodactyl.files.max_edit_size')
        );

        return new Response($response, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    /**
     * Generates a one-time token with a link that the user can use to
     * download a given file.
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function download(GetFileContentsRequest $request, Server $server)
    {
        $token = $server->audit(AuditLog::SERVER__FILESYSTEM_DOWNLOAD, function (AuditLog $audit, Server $server) use ($request) {
            $audit->metadata = ['file' => $request->get('file')];

            return $this->jwtService
                ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
                ->setClaims([
                    'file_path' => rawurldecode($request->get('file')),
                    'server_uuid' => $server->uuid,
                ])
                ->handle($server->node, $request->user()->id . $server->uuid);
        });

        return [
            'object' => 'signed_url',
            'attributes' => [
                'url' => sprintf(
                    '%s/download/file?token=%s',
                    $server->node->getConnectionAddress(),
                    $token->toString()
                ),
            ],
        ];
    }

    /**
     * Writes the contents of the specified file to the server.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function write(WriteFileContentRequest $request, Server $server): JsonResponse
    {
        $server->audit(AuditLog::SERVER__FILESYSTEM_WRITE, function (AuditLog $audit, Server $server) use ($request) {
            $audit->subaction = 'write_content';
            $audit->metadata = ['file' => $request->get('file')];

            $this->fileRepository
                ->setServer($server)
                ->putContent($request->get('file'), $request->getContent());
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Creates a new folder on the server.
     *
     * @throws \Throwable
     */
    public function create(CreateFolderRequest $request, Server $server): JsonResponse
    {
        $server->audit(AuditLog::SERVER__FILESYSTEM_WRITE, function (AuditLog $audit, Server $server) use ($request) {
            $audit->subaction = 'create_folder';
            $audit->metadata = ['file' => $request->input('root', '/') . $request->input('name')];

            $this->fileRepository
                ->setServer($server)
                ->createDirectory($request->input('name'), $request->input('root', '/'));
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Renames a file on the remote machine.
     *
     * @throws \Throwable
     */
    public function rename(RenameFileRequest $request, Server $server): JsonResponse
    {
        $server->audit(AuditLog::SERVER__FILESYSTEM_RENAME, function (AuditLog $audit, Server $server) use ($request) {
            $audit->metadata = ['root' => $request->input('root'), 'files' => $request->input('files')];

            $this->fileRepository
                ->setServer($server)
                ->renameFiles($request->input('root'), $request->input('files'));
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Copies a file on the server.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function copy(CopyFileRequest $request, Server $server): JsonResponse
    {
        $server->audit(AuditLog::SERVER__FILESYSTEM_WRITE, function (AuditLog $audit, Server $server) use ($request) {
            $audit->subaction = 'copy_file';
            $audit->metadata = ['file' => $request->input('location')];

            $this->fileRepository
                ->setServer($server)
                ->copyFile($request->input('location'));
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function compress(CompressFilesRequest $request, Server $server): array
    {
        $file = $server->audit(AuditLog::SERVER__FILESYSTEM_COMPRESS, function (AuditLog $audit, Server $server) use ($request) {
            // Allow up to five minutes for this request to process before timing out.
            set_time_limit(300);

            $audit->metadata = ['root' => $request->input('root'), 'files' => $request->input('files')];

            return $this->fileRepository->setServer($server)
                ->compressFiles(
                    $request->input('root'),
                    $request->input('files')
                );
        });

        return $this->fractal->item($file)
            ->transformWith($this->getTransformer(FileObjectTransformer::class))
            ->toArray();
    }

    /**
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function decompress(DecompressFilesRequest $request, Server $server): JsonResponse
    {
        $file = $server->audit(AuditLog::SERVER__FILESYSTEM_DECOMPRESS, function (AuditLog $audit, Server $server) use ($request) {
            // Allow up to five minutes for this request to process before timing out.
            set_time_limit(300);

            $audit->metadata = ['root' => $request->input('root'), 'files' => $request->input('file')];

            $this->fileRepository->setServer($server)
                ->decompressFile($request->input('root'), $request->input('file'));
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Deletes files or folders for the server in the given root directory.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function delete(DeleteFileRequest $request, Server $server): JsonResponse
    {
        $server->audit(AuditLog::SERVER__FILESYSTEM_DELETE, function (AuditLog $audit, Server $server) use ($request) {
            $audit->metadata = ['root' => $request->input('root'), 'files' => $request->input('files')];

            $this->fileRepository->setServer($server)
                ->deleteFiles(
                    $request->input('root'),
                    $request->input('files')
                );
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Updates file permissions for file(s) in the given root directory.
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function chmod(ChmodFilesRequest $request, Server $server): JsonResponse
    {
        $this->fileRepository->setServer($server)
            ->chmodFiles(
                $request->input('root'),
                $request->input('files')
            );

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Requests that a file be downloaded from a remote location by Wings.
     *
     * @param $request
     *
     * @throws \Throwable
     */
    public function pull(PullFileRequest $request, Server $server): JsonResponse
    {
        $server->audit(AuditLog::SERVER__FILESYSTEM_PULL, function (AuditLog $audit, Server $server) use ($request) {
            $audit->metadata = ['directory' => $request->input('directory'), 'url' => $request->input('url')];

            $this->fileRepository->setServer($server)->pull($request->input('url'), $request->input('directory'));
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
