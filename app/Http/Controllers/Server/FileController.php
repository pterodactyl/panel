<?php

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class FileController extends Controller
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface
     */
    private $fileRepository;

    /**
     * FileController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface $fileRepository
     */
    public function __construct(FileRepositoryInterface $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function index(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');
        $this->authorize('list-files', $server);

        $requestDirectory = '/' . trim(urldecode($request->route()->parameter('directory', '/')), '/');

        try {
            $contents = $this->fileRepository->setServer($server)->setToken(
                $request->attributes->get('server_token')
            )->getDirectory($requestDirectory);
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception, true);
        }

        return JsonResponse::create([
            'contents' => $contents,
            'editable' => config('pterodactyl.files.editable'),
            'current_directory' => $requestDirectory,
        ]);
    }
}
