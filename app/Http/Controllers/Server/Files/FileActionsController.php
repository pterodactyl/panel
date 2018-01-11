<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Http\Controllers\Server\Files;

use Illuminate\View\View;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Http\Requests\Server\UpdateFileContentsFormRequest;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class FileActionsController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface
     */
    protected $repository;

    /**
     * FileActionsController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface $repository
     */
    public function __construct(FileRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display server file index list.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('list-files', $server);

        $this->setRequest($request)->injectJavascript([
            'meta' => [
                'directoryList' => route('server.files.directory-list', $server->uuidShort),
                'csrftoken' => csrf_token(),
            ],
            'permissions' => [
                'moveFiles' => $request->user()->can('move-files', $server),
                'copyFiles' => $request->user()->can('copy-files', $server),
                'compressFiles' => $request->user()->can('compress-files', $server),
                'decompressFiles' => $request->user()->can('decompress-files', $server),
                'createFiles' => $request->user()->can('create-files', $server),
                'downloadFiles' => $request->user()->can('download-files', $server),
                'deleteFiles' => $request->user()->can('delete-files', $server),
            ],
        ]);

        return view('server.files.index');
    }

    /**
     * Render page to manually create a file in the panel.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request): View
    {
        $this->authorize('create-files', $request->attributes->get('server'));
        $this->setRequest($request)->injectJavascript();

        return view('server.files.add', [
            'directory' => (in_array($request->get('dir'), [null, '/', ''])) ? '' : trim($request->get('dir'), '/') . '/',
        ]);
    }

    /**
     * Display a form to allow for editing of a file.
     *
     * @param \Pterodactyl\Http\Requests\Server\UpdateFileContentsFormRequest $request
     * @param string                                                          $uuid
     * @param string                                                          $file
     * @return \Illuminate\View\View
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function view(UpdateFileContentsFormRequest $request, string $uuid, string $file): View
    {
        $server = $request->attributes->get('server');

        $dirname = pathinfo($file, PATHINFO_DIRNAME);
        try {
            $content = $this->repository->setServer($server)->setToken($request->attributes->get('server_token'))->getContent($file);
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
        }

        $this->setRequest($request)->injectJavascript(['stat' => $request->attributes->get('file_stats')]);

        return view('server.files.edit', [
            'file' => $file,
            'stat' => $request->attributes->get('file_stats'),
            'contents' => $content,
            'directory' => (in_array($dirname, ['.', './', '/'])) ? '/' : trim($dirname, '/') . '/',
        ]);
    }
}
