<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Http\Controllers\Server\Files;

use Illuminate\Log\Writer;
use Illuminate\Http\Request;
use Illuminate\Contracts\Session\Session;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Traits\Controllers\JavascriptInjection;
use Pterodactyl\Http\Requests\Server\UpdateFileContentsFormRequest;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;

class FileActionsController extends Controller
{
    use JavascriptInjection;

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
     * FileActionsController constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface $fileRepository
     * @param \Illuminate\Contracts\Session\Session                            $session
     * @param \Illuminate\Log\Writer                                           $writer
     */
    public function __construct(FileRepositoryInterface $fileRepository, Session $session, Writer $writer)
    {
        $this->fileRepository = $fileRepository;
        $this->session = $session;
        $this->writer = $writer;
    }

    /**
     * Display server file index list.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('list-files', $server);

        $this->injectJavascript([
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
    public function create(Request $request)
    {
        $this->authorize('create-files', $this->session->get('server_data.model'));
        $this->injectJavascript();

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateFileContentsFormRequest $request, $uuid, $file)
    {
        $server = $this->session->get('server_data.model');
        $this->authorize('edit-files', $server);

        $dirname = pathinfo($file, PATHINFO_DIRNAME);
        try {
            $content = $this->fileRepository->setNode($server->node_id)
                ->setAccessServer($server->uuid)
                ->setAccessToken($this->session->get('server_data.token'))
                ->getContent($file);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            $this->writer->warning($exception);

            throw new DisplayException(trans('exceptions.daemon_connection_failed', [
                'code' => is_null($response) ? 'E_CONN_REFUSED' : $response->getStatusCode(),
            ]));
        }

        $this->injectJavascript(['stat' => $request->getStats()]);

        return view('server.files.edit', [
            'file' => $file,
            'stat' => $request->getStats(),
            'contents' => $content,
            'directory' => (in_array($dirname, ['.', './', '/'])) ? '/' : trim($dirname, '/') . '/',
        ]);
    }
}
