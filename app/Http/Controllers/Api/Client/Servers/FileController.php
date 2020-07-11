<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Transformers\Daemon\FileObjectTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CopyFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\ListFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DeleteFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\RenameFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CreateFolderRequest;
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
     *
     * @param \Illuminate\Contracts\Routing\ResponseFactory $responseFactory
     * @param \Pterodactyl\Services\Nodes\NodeJWTService $jwtService
     * @param \Pterodactyl\Repositories\Wings\DaemonFileRepository $fileRepository
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
     * Generates a one-time token with a link that the user can use to
     * download a given file.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Files\GetFileContentsRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Exception
     */
    public function download(GetFileContentsRequest $request, Server $server)
    {
        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setClaims([
                'file_path' => $request->get('file'),
                'server_uuid' => $server->uuid,
            ])
            ->handle($server->node, $request->user()->id . $server->uuid);

        return [
            'object' => 'signed_url',
            'attributes' => [
                'url' => sprintf(
                    '%s/download/file?token=%s',
                    $server->node->getConnectionAddress(),
                    $token->__toString()
                ),
            ],
        ];
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
            ->createDirectory($request->input('name'), $request->input('root', '/'));

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
}
