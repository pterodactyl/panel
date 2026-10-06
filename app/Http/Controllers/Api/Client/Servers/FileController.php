<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Files\ChangesFilePermissions;
use Pterodactyl\Contracts\Files\CompressesFiles;
use Pterodactyl\Contracts\Files\CopiesFiles;
use Pterodactyl\Contracts\Files\CreatesDirectories;
use Pterodactyl\Contracts\Files\DecompressesFiles;
use Pterodactyl\Contracts\Files\DeletesFiles;
use Pterodactyl\Contracts\Files\ListsDirectories;
use Pterodactyl\Contracts\Files\PullsFiles;
use Pterodactyl\Contracts\Files\ReadsFileContents;
use Pterodactyl\Contracts\Files\RenamesFiles;
use Pterodactyl\Contracts\Files\WritesFileContents;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\ChmodFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CompressFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CopyFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\CreateFolderRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DecompressFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\DeleteFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\GetFileContentsRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\ListFilesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\PullFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\RenameFileRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Files\WriteFileContentRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Client\FileObjectTransformer;
use Throwable;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Files', 'Browse, read, mutate, archive, and transfer server files through Wings.')]
#[ResponseField('attributes.mode', nullable: true)]
#[ResponseField('attributes.mode_bits', nullable: true)]
#[ResponseField('attributes.size', 'integer', nullable: true)]
class FileController extends ClientApiController
{
    private const array FILE_OBJECT_EXAMPLE = [
        'object' => 'file_object',
        'attributes' => [
            'name' => 'server.properties',
            'mode' => '-rw-r--r--',
            'mode_bits' => '0644',
            'size' => 1024,
            'is_file' => true,
            'is_symlink' => false,
            'mimetype' => 'text/plain',
            'created_at' => '2026-06-29T12:00:00+00:00',
            'modified_at' => '2026-06-29T12:00:00+00:00',
        ],
    ];

    private const array ARCHIVE_OBJECT_EXAMPLE = [
        'object' => 'file_object',
        'attributes' => [
            'name' => 'archive-2026-06-29.tar.gz',
            'mode' => '-rw-r--r--',
            'mode_bits' => '0644',
            'size' => 4096,
            'is_file' => true,
            'is_symlink' => false,
            'mimetype' => 'application/gzip',
            'created_at' => '2026-06-29T12:00:00+00:00',
            'modified_at' => '2026-06-29T12:00:00+00:00',
        ],
    ];

    private const array FILE_LIST_EXAMPLE = [
        'object' => 'list',
        'data' => [
            self::FILE_OBJECT_EXAMPLE,
        ],
    ];

    private const array SIGNED_URL_EXAMPLE = [
        'object' => 'signed_url',
        'attributes' => [
            'url' => 'https://node.example.test/download/file?token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.example.signature',
        ],
    ];

    private const array DAEMON_ERROR = [
        'errors' => [
            [
                'code' => 'DaemonConnectionException',
                'status' => '502',
                'detail' => 'There was an error while communicating with the machine running this server.',
            ],
        ],
    ];

    /**
     * Returns a listing of files in a given directory.
     *
     *
     * @return ApiPayload
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('List files', 'Returns files and folders in a server directory.')]
    #[QueryParam('directory', 'string', 'Directory to list. Defaults to the server root.', required: false, example: '/config', nullable: true)]
    #[ScribeResponse(self::FILE_LIST_EXAMPLE, description: 'Directory contents returned.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not list the directory.')]
    public function directory(ListFilesRequest $request, ListsDirectories $operation, Server $server): array
    {
        $directory = JsonValueGuard::nullableString($request->validated('directory')) ?? '/';
        $contents = $operation->list($server, $directory);

        return Fractal::collection($contents)
            ->transformWith($this->getTransformer(FileObjectTransformer::class))
            ->toResponseArray();
    }

    /**
     * Return the contents of a specified file for the user.
     *
     * @throws Throwable
     */
    #[Endpoint('Get file contents', 'Returns raw text contents for a server file.')]
    #[QueryParam('file', 'string', 'Path to the file to read.', required: true, example: '/server.properties')]
    #[ScribeResponse("motd=A Minecraft Server\nserver-port=25565", description: 'File contents returned as text/plain.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not read the file.')]
    public function contents(GetFileContentsRequest $request, ReadsFileContents $operation, Server $server): Response
    {
        $file = JsonValueGuard::string($request->validated('file'));
        $response = $operation->read($server, $file);

        Activity::event('server:file.read')->property('file', $file)->log();

        return new Response($response, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    /**
     * Generates a one-time token with a link that the user can use to
     * download a given file.
     *
     *
     * @return array{object: string, attributes: array{url: string}}
     *
     * @throws Throwable
     */
    #[Endpoint('Get file download URL', 'Returns a short-lived signed URL for downloading a server file directly from Wings.')]
    #[QueryParam('file', 'string', 'Path to the file to download.', required: true, example: '/server.properties')]
    #[ScribeResponse(self::SIGNED_URL_EXAMPLE, description: 'Signed download URL returned.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not create the download URL.')]
    public function download(GetFileContentsRequest $request, NodeJWTService $jwtService, Server $server): array
    {
        $file = JsonValueGuard::string($request->validated('file'));
        $token = $jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setUser($request->user())
            ->setClaims([
                'file_path' => rawurldecode($file),
                'server_uuid' => $server->uuid,
            ])
            ->setScopes(JwtScope::FileDownload)
            ->handle($server->node, $request->user()->id.$server->uuid);

        Activity::event('server:file.download')->property('file', $file)->log();

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
     * @throws DaemonConnectionException
     */
    #[Endpoint('Write file contents', 'Writes the raw request body to a server file. This endpoint does not expect a JSON wrapper for the file contents.')]
    #[QueryParam('file', 'string', 'Path to the file to write.', required: true, example: '/server.properties')]
    #[ScribeResponse(status: 204, description: 'File contents written.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not write the file.')]
    public function write(WriteFileContentRequest $request, WritesFileContents $operation, Server $server): JsonResponse
    {
        $file = JsonValueGuard::string($request->validated('file'));
        $operation->write($server, $file, $request->getContent());

        Activity::event('server:file.write')->property('file', $file)->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Creates a new folder on the server.
     *
     * @throws Throwable
     */
    #[Endpoint('Create folder', 'Creates a folder in a server directory.')]
    #[ScribeResponse(status: 204, description: 'Folder created.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not create the folder.')]
    public function create(CreateFolderRequest $request, CreatesDirectories $operation, Server $server): JsonResponse
    {
        $name = JsonValueGuard::string($request->validated('name'));
        $root = JsonValueGuard::string($request->validated('root', '/'));
        $operation->create($server, $name, $root);

        Activity::event('server:file.create-directory')
            ->property('name', $name)
            ->property('directory', $root)
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Renames a file on the remote machine.
     *
     * @throws Throwable
     */
    #[Endpoint('Rename files', 'Renames one or more files or folders in a server directory.')]
    #[ScribeResponse(status: 204, description: 'Files renamed.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not rename the files.')]
    public function rename(RenameFileRequest $request, RenamesFiles $operation, Server $server): JsonResponse
    {
        $root = JsonValueGuard::nullableString($request->validated('root'));
        $files = JsonValueGuard::jsonArray($request->validated('files'));
        $operation->rename($server, $root, $files);

        Activity::event('server:file.rename')
            ->property('directory', $root)
            ->property('files', $files)
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Copies a file on the server.
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('Copy file', 'Creates a copy of a file on the server.')]
    #[ScribeResponse(status: 204, description: 'File copied.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not copy the file.')]
    public function copy(CopyFileRequest $request, CopiesFiles $operation, Server $server): JsonResponse
    {
        $location = JsonValueGuard::string($request->validated('location'));
        $operation->copy($server, $location);

        Activity::event('server:file.copy')->property('file', $location)->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @return ApiPayload
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('Compress files', 'Creates an archive from one or more files or folders in a server directory.')]
    #[ScribeResponse(self::ARCHIVE_OBJECT_EXAMPLE, description: 'Archive created.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not create the archive.')]
    public function compress(CompressFilesRequest $request, CompressesFiles $operation, Server $server): array
    {
        $root = JsonValueGuard::nullableString($request->validated('root'));
        $files = JsonValueGuard::jsonArray($request->validated('files'));
        $file = $operation->compress($server,
            $root,
            $files
        );

        Activity::event('server:file.compress')
            ->property('directory', $root)
            ->property('files', $files)
            ->log();

        return Fractal::item($file)
            ->transformWith($this->getTransformer(FileObjectTransformer::class))
            ->toResponseArray();
    }

    /**
     * @throws DaemonConnectionException
     */
    #[Endpoint('Decompress file', 'Extracts an archive in a server directory.')]
    #[ScribeResponse(status: 204, description: 'Archive extracted.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not extract the archive.')]
    public function decompress(DecompressFilesRequest $request, DecompressesFiles $operation, Server $server): JsonResponse
    {
        $root = JsonValueGuard::nullableString($request->validated('root'));
        $file = JsonValueGuard::string($request->validated('file'));

        $operation->decompress($server,
            $root,
            $file
        );

        Activity::event('server:file.decompress')
            ->property('directory', $root)
            ->property('files', $file)
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Deletes files or folders for the server in the given root directory.
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('Delete files', 'Deletes one or more files or folders from a server directory.')]
    #[ScribeResponse(status: 204, description: 'Files deleted.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not delete the files.')]
    public function delete(DeleteFileRequest $request, DeletesFiles $operation, Server $server): JsonResponse
    {
        $root = JsonValueGuard::nullableString($request->validated('root'));
        $files = JsonValueGuard::jsonArray($request->validated('files'));
        $operation->delete($server,
            $root,
            $files
        );

        Activity::event('server:file.delete')
            ->property('directory', $root)
            ->property('files', $files)
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Updates file permissions for file(s) in the given root directory.
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('Change file permissions', 'Updates POSIX mode bits for one or more files or folders in a server directory.')]
    #[ScribeResponse(status: 204, description: 'File permissions updated.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not update file permissions.')]
    public function chmod(ChmodFilesRequest $request, ChangesFilePermissions $operation, Server $server): JsonResponse
    {
        $root = JsonValueGuard::nullableString($request->validated('root'));
        $files = JsonValueGuard::jsonArray($request->validated('files'));
        $operation->change($server,
            $root,
            $files
        );

        Activity::event('server:file.chmod')
            ->property('directory', $root)
            ->property('files', $files)
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Requests that a file be downloaded from a remote location by Wings.
     *
     * @throws Throwable
     */
    #[Endpoint('Pull remote file', 'Requests that Wings download a remote file into a server directory.')]
    #[ScribeResponse(status: 204, description: 'Remote file pull queued or completed.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not pull the remote file.')]
    public function pull(PullFileRequest $request, PullsFiles $operation, Server $server): JsonResponse
    {
        $url = JsonValueGuard::string($request->validated('url'));
        $directory = JsonValueGuard::nullableString($request->validated('directory'));
        $operation->pull($server,
            $url,
            $directory,
            JsonValueGuard::jsonArray($request->safe()->only(['filename', 'use_header', 'foreground']))
        );

        Activity::event('server:file.pull')
            ->property('directory', $directory)
            ->property('url', $url)
            ->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
