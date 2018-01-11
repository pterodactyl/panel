<?php

namespace Pterodactyl\Http\Controllers\Server\Files;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class RemoteRequestController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface
     */
    protected $repository;

    /**
     * RemoteRequestController constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                          $config
     * @param \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface $repository
     */
    public function __construct(ConfigRepository $config, FileRepositoryInterface $repository)
    {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Return a listing of a servers file directory.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function directory(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('list-files', $server);

        $requestDirectory = '/' . trim(urldecode($request->input('directory', '/')), '/');
        $directory = [
            'header' => $requestDirectory !== '/' ? $requestDirectory : '',
            'first' => $requestDirectory !== '/',
        ];

        $goBack = explode('/', trim($requestDirectory, '/'));
        if (! empty(array_filter($goBack)) && count($goBack) >= 2) {
            array_pop($goBack);

            $directory['show'] = true;
            $directory['link'] = '/' . implode('/', $goBack);
            $directory['link_show'] = implode('/', $goBack) . '/';
        }

        try {
            $listing = $this->repository->setServer($server)->setToken($request->attributes->get('server_token'))->getDirectory($requestDirectory);
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception, true);
        }

        return view('server.files.list', [
            'files' => $listing['files'],
            'folders' => $listing['folders'],
            'editableMime' => $this->config->get('pterodactyl.files.editable'),
            'directory' => $directory,
        ]);
    }

    /**
     * Put the contents of a file onto the daemon.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function store(Request $request): Response
    {
        $server = $request->attributes->get('server');
        $this->authorize('save-files', $server);

        try {
            $this->repository->setServer($server)->setToken($request->attributes->get('server_token'))
                ->putContent($request->input('file'), $request->input('contents') ?? '');

            return response('', 204);
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
