<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Server\Files;

use Illuminate\Log\Writer;
use Illuminate\Http\Request;
use Illuminate\Contracts\Session\Session;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;

class RemoteRequestController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface
     */
    protected $fileRepository;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * RemoteRequestController constructor.
     *
     * @param \Illuminate\Contracts\Config\Repository                          $config
     * @param \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface $fileRepository
     * @param \Illuminate\Contracts\Session\Session                            $session
     * @param \Illuminate\Log\Writer                                           $writer
     */
    public function __construct(
        ConfigRepository $config,
        FileRepositoryInterface $fileRepository,
        Session $session,
        Writer $writer
    ) {
        $this->config = $config;
        $this->fileRepository = $fileRepository;
        $this->session = $session;
        $this->writer = $writer;
    }

    /**
     * Return a listing of a servers file directory.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function directory(Request $request)
    {
        $server = $this->session->get('server_data.model');
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
            $listing = $this->fileRepository->setNode($server->node_id)
                ->setAccessServer($server->uuid)
                ->setAccessToken($this->session->get('server_data.token'))
                ->getDirectory($requestDirectory);
        } catch (RequestException $exception) {
            $this->writer->warning($exception);
            $response = $exception->getResponse();

            return response()->json(['error' => trans('exceptions.daemon_connection_failed', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ])], 500);
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
     * @param string                   $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function store(Request $request, $uuid)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('save-files', $server);

        try {
            $this->fileRepository->setNode($server->node_id)
                ->setAccessServer($server->uuid)
                ->setAccessToken($this->session->get('server_data.token'))
                ->putContent($request->input('file'), $request->input('contents'));

            return response('', 204);
        } catch (RequestException $exception) {
            $this->writer->warning($exception);
            $response = $exception->getResponse();

            return response()->json(['error' => trans('exceptions.daemon_connection_failed', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ])], 500);
        }
    }
}
